<?php

namespace Botble\SocialLogin\Http\Controllers\API;

use Botble\Base\Http\Controllers\BaseController;
use Botble\SocialLogin\Facades\SocialService;
use Botble\SocialLogin\Services\SocialLoginService;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class XLoginController extends BaseController
{
    public function __construct(protected SocialLoginService $socialLoginService)
    {
    }

    public function login(Request $request)
    {
        try {
            logger()->info('X (Twitter) login API called', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'has_access_token' => $request->has('accessToken'),
                'guard' => $request->input('guard', 'web'),
            ]);

            $request->validate([
                'accessToken' => ['required', 'string'],
                'guard' => ['string', 'nullable'],
            ]);

            $accessToken = $request->input('accessToken');
            $guard = $request->input('guard', 'web');

            logger()->info('X login validation passed', [
                'guard' => $guard,
                'token_length' => strlen($accessToken),
                'token_prefix' => substr($accessToken, 0, 20) . '...',
            ]);

            $clientId = SocialService::setting('x_app_id');
            $clientSecret = SocialService::setting('x_app_secret');

            logger()->info('X OAuth settings retrieved', [
                'has_client_id' => ! empty($clientId),
                'has_client_secret' => ! empty($clientSecret),
                'client_id_length' => $clientId ? strlen($clientId) : 0,
            ]);

            if (! $clientId || ! $clientSecret) {
                logger()->error('X OAuth configuration missing', [
                    'client_id_empty' => empty($clientId),
                    'client_secret_empty' => empty($clientSecret),
                ]);

                return $this->httpResponse()
                    ->setError()
                    ->setMessage(__('X (Twitter) authentication is not properly configured'))
                    ->toApiResponse();
            }

            $xUserData = $this->verifyXToken($accessToken);

            if (! $xUserData) {
                logger()->error('X token verification failed: ' . $accessToken);

                return $this->httpResponse()
                    ->setError()
                    ->setMessage(__('Invalid X (Twitter) token'))
                    ->toApiResponse();
            }

            logger()->info('X token verified successfully', [
                'user_data_keys' => array_keys($xUserData),
                'has_email' => isset($xUserData['email']),
                'has_id' => isset($xUserData['id']),
                'has_name' => isset($xUserData['name']),
                'has_username' => isset($xUserData['username']),
            ]);

            $email = $xUserData['email'] ?? null;
            $xId = $xUserData['id'] ?? null;

            if (! $email || ! $xId) {
                logger()->error('Missing required X user data', [
                    'email_present' => ! empty($email),
                    'x_id_present' => ! empty($xId),
                    'available_fields' => array_keys($xUserData),
                ]);

                return $this->httpResponse()
                    ->setError()
                    ->setMessage(__('Cannot login, no email or X ID provided!'))
                    ->toApiResponse();
            }

            $providerData = SocialService::supportedModules()[$guard] ?? null;

            logger()->info('Provider data lookup', [
                'guard' => $guard,
                'provider_found' => ! empty($providerData),
                'available_guards' => array_keys(SocialService::supportedModules()),
            ]);

            if (! $providerData) {
                logger()->error('Invalid guard configuration', [
                    'requested_guard' => $guard,
                    'available_guards' => array_keys(SocialService::supportedModules()),
                ]);

                return $this->httpResponse()
                    ->setError()
                    ->setMessage(__('Invalid guard configuration'))
                    ->toApiResponse();
            }

            $model = new $providerData['model']();

            logger()->info('Looking up existing user accounts', [
                'email' => $email,
                'x_id' => $xId,
                'model_class' => $model::class,
            ]);

            $account = $this->socialLoginService->findUserByEmail($email, $model::class);
            $socialLoginUser = $this->socialLoginService->findUserByProvider('x', $xId);

            logger()->info('User lookup results', [
                'account_found_by_email' => ! empty($account),
                'social_login_found' => ! empty($socialLoginUser),
                'account_id' => $account?->getKey(),
                'social_user_id' => $socialLoginUser?->user?->getKey(),
            ]);

            if ($socialLoginUser && ! $account) {
                $account = $socialLoginUser->user;
                logger()->info('Using account from social login user', [
                    'account_id' => $account?->getKey(),
                ]);
            }

            if (! $account) {
                logger()->info('Creating new user account', [
                    'email' => $email,
                    'name_from_x' => $xUserData['name'] ?? null,
                    'username_from_x' => $xUserData['username'] ?? null,
                ]);

                $data = [
                    'name' => $xUserData['name'] ?? $xUserData['username'] ?? $email,
                    'email' => $email,
                    'password' => Hash::make(Str::random(36)),
                ];

                $data = apply_filters('social_login_before_saving_account', $data, (object) $xUserData, $providerData);

                logger()->info('Account data prepared', [
                    'data_keys' => array_keys($data),
                    'name' => $data['name'],
                    'email' => $data['email'],
                ]);

                $account = $model;
                $account->fill($data);
                $account->confirmed_at = Carbon::now();
                $account->save();

                logger()->info('New account created', [
                    'account_id' => $account->getKey(),
                    'email' => $account->email,
                    'name' => $account->name,
                ]);

                event(new Registered($account));
            } else {
                logger()->info('Using existing account', [
                    'account_id' => $account->getKey(),
                    'email' => $account->email,
                ]);
            }

            $socialLoginData = $this->socialLoginService->createSocialLoginData([
                'provider' => 'x',
                'id' => $xId,
                'token' => $accessToken,
                'refresh_token' => null,
                'expires_in' => null,
                'name' => $xUserData['name'] ?? $xUserData['username'] ?? $account->name,
                'email' => $email,
                'avatar' => $xUserData['profile_image_url'] ?? null,
            ]);

            logger()->info('Social login data created', [
                'provider' => 'x',
                'x_id' => $xId,
                'has_avatar' => ! empty($xUserData['profile_image_url']),
                'social_name' => $socialLoginData['provider_data']['name'] ?? 'unknown',
            ]);

            $hasSocialLogin = $this->socialLoginService->hasSocialLogin($account, 'x');

            if ($hasSocialLogin) {
                logger()->info('Updating existing social login');
                $this->socialLoginService->updateSocialLogin($account, 'x', $socialLoginData);
            } else {
                logger()->info('Adding new social login');
                $this->socialLoginService->addSocialLogin($account, $socialLoginData);
            }

            logger()->info('Creating authentication token for user', [
                'account_id' => $account->getKey(),
            ]);

            $token = $account->createToken('x-login')->plainTextToken;

            logger()->info('X login successful', [
                'account_id' => $account->getKey(),
                'email' => $account->email,
                'token_created' => ! empty($token),
            ]);

            return $this->httpResponse()
                ->setData([
                    'token' => $token,
                    'user' => [
                        'id' => $account->getKey(),
                        'name' => $account->name,
                        'email' => $account->email,
                    ],
                ])
                ->setMessage(__('Login successful'))
                ->toApiResponse();

        } catch (ValidationException $e) {
            logger()->error('X login validation error', [
                'errors' => $e->errors(),
                'message' => $e->getMessage(),
            ]);

            return $this->httpResponse()
                ->setError()
                ->setMessage($e->getMessage())
                ->withInput()
                ->toApiResponse();
        } catch (Exception $e) {
            logger()->error('X login error: ' . $e->getMessage(), [
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->httpResponse()
                ->setError()
                ->setMessage(__('X (Twitter) token invalid'))
                ->toApiResponse();
        }
    }

    protected function verifyXToken(string $accessToken): ?array
    {
        try {
            logger()->info('Starting X token verification', [
                'token_length' => strlen($accessToken),
                'token_starts_with' => substr($accessToken, 0, 20) . '...',
            ]);

            // Get user info from X API v2
            $userData = $this->getUserInfoFromX($accessToken);

            if ($userData) {
                logger()->info('X user info retrieved successfully', [
                    'user_id' => $userData['id'] ?? 'missing',
                    'user_email' => $userData['email'] ?? 'missing',
                    'username' => $userData['username'] ?? 'missing',
                ]);

                return $userData;
            }

            logger()->error('X token verification failed');

            return null;

        } catch (Exception $e) {
            logger()->error('X token verification failed: ' . $e->getMessage(), [
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    protected function getUserInfoFromX(string $accessToken): ?array
    {
        try {
            $userInfoUrl = 'https://api.x.com/2/users/me';

            logger()->info('Making X user info request', [
                'url' => $userInfoUrl,
                'fields' => 'id,name,username,email,profile_image_url',
            ]);

            $userResponse = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])
                ->get($userInfoUrl, [
                    'user.fields' => 'id,name,username,email,profile_image_url',
                ]);

            if (! $userResponse->successful()) {
                logger()->error('X user info request failed', [
                    'status' => $userResponse->status(),
                    'response' => $userResponse->body(),
                ]);

                return null;
            }

            logger()->info('X user info request successful');

            $responseData = $userResponse->json();
            $userData = $responseData['data'] ?? null;

            if (! $userData) {
                logger()->error('X user info response missing data', [
                    'response' => $responseData,
                ]);

                return null;
            }

            logger()->info('X user data received', [
                'user_data_keys' => array_keys($userData),
                'has_id' => isset($userData['id']),
                'has_email' => isset($userData['email']),
                'has_name' => isset($userData['name']),
                'has_username' => isset($userData['username']),
                'user_id' => $userData['id'] ?? 'missing',
                'user_email' => $userData['email'] ?? 'missing',
                'username' => $userData['username'] ?? 'missing',
            ]);

            if (! isset($userData['id'])) {
                logger()->error('X user data missing ID', [
                    'user_data' => $userData,
                ]);

                return null;
            }

            return $userData;

        } catch (Exception $e) {
            logger()->error('X user info request failed: ' . $e->getMessage(), [
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return null;
        }
    }
}
