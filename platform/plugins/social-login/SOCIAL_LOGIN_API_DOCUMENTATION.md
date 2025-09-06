# Social Login API Documentation

This document describes the API endpoints available for social login authentication from mobile applications (Flutter, React Native, etc.).

## Overview

These API endpoints allow users to authenticate using social login services (Apple, Google, Facebook, and X/Twitter). They verify identity/access tokens and create or log in users to the system.

## Endpoints

### Apple Login
```
POST /api/v1/auth/apple
```

### Google Login
```
POST /api/v1/auth/google
```

### Facebook Login
```
POST /api/v1/auth/facebook
```

### X (Twitter) Login
```
POST /api/v1/auth/x
```

## Request Headers

```
Content-Type: application/json
Accept: application/json
```

## Request Body

### Apple Login
```json
{
    "identityToken": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...", // Required: Apple's identity token
    "guard": "customer" // Optional: Authentication guard (defaults to 'web')
}
```

### Google Login
```json
{
    "identityToken": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...", // Required: Google's ID token
    "guard": "customer" // Optional: Authentication guard (defaults to 'web')
}
```

### Facebook Login
```json
{
    "accessToken": "EAABwzLixnjYBO...", // Required: Facebook's access token
    "guard": "customer" // Optional: Authentication guard (defaults to 'web')
}
```

### X (Twitter) Login
```json
{
    "accessToken": "AAAAAAAAAAAAAAAAAAAAAMLheAAAAAAA0%2BuSeid...", // Required: X's access token
    "guard": "customer" // Optional: Authentication guard (defaults to 'web')
}
```

## Response

### Success Response (200)

```json
{
    "error": false,
    "data": {
        "token": "1|abc123...", // Sanctum API token
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com"
        }
    },
    "message": "Login successful"
}
```

### Error Response (400/422)

```json
{
    "error": true,
    "message": "Invalid Apple token",
    "data": null
}
```

## Implementation Details

### How it works

#### Apple Login
1. **Token Verification**: Verifies Apple identity token using Apple's public keys from `https://appleid.apple.com/auth/keys`
2. **User Lookup**: Searches for existing users by email or Apple ID
3. **User Creation**: Creates new users if they don't exist
4. **Social Login Record**: Creates or updates social login records
5. **API Token**: Returns a Sanctum API token for subsequent requests

#### Google Login
1. **Token Verification**: Verifies Google ID token using Google Auth Library
2. **User Lookup**: Searches for existing users by email or Google ID
3. **User Creation**: Creates new users if they don't exist
4. **Social Login Record**: Creates or updates social login records
5. **API Token**: Returns a Sanctum API token for subsequent requests

#### Facebook Login
1. **Token Verification**: Verifies Facebook access token using Facebook Graph API
2. **User Lookup**: Searches for existing users by email or Facebook ID
3. **User Creation**: Creates new users if they don't exist
4. **Social Login Record**: Creates or updates social login records
5. **API Token**: Returns a Sanctum API token for subsequent requests

#### X (Twitter) Login
1. **Token Verification**: Verifies X access token using X API v2
2. **User Lookup**: Searches for existing users by email or X ID
3. **User Creation**: Creates new users if they don't exist
4. **Social Login Record**: Creates or updates social login records
5. **API Token**: Returns a Sanctum API token for subsequent requests

### Security Features

#### Apple Login
- JWT signature verification using Apple's public keys
- Token expiration validation
- Issuer validation (must be from Apple)
- JWKS caching to reduce external API calls

#### Google Login
- ID token verification using Google Auth Library
- Audience validation (client ID verification)
- Issuer validation (must be from Google)
- Token expiration validation

#### Facebook Login
- Access token verification using Facebook Graph API
- App ID validation
- User ID validation
- Token validity checks

#### X (Twitter) Login
- Access token verification using X API v2
- Bearer token validation
- User data retrieval and validation
- API rate limiting compliance

## Usage Examples

### JavaScript - Apple Login

```javascript
// After getting identity token from Apple's Sign in with Apple
const response = await fetch('/api/v1/auth/apple', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    body: JSON.stringify({
        identityToken: appleIdentityToken, // From Apple's response
        guard: 'customer'
    })
});

const result = await response.json();

if (!result.error) {
    // Store the API token for future requests
    localStorage.setItem('api_token', result.data.token);

    // User is now logged in
    console.log('User:', result.data.user);
} else {
    console.error('Login failed:', result.message);
}
```

### JavaScript - Google Login

```javascript
// After getting ID token from Google Sign-In
const response = await fetch('/api/v1/auth/google', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    body: JSON.stringify({
        identityToken: googleidentityToken, // From Google's response
        guard: 'customer'
    })
});

const result = await response.json();

if (!result.error) {
    // Store the API token for future requests
    localStorage.setItem('api_token', result.data.token);

    // User is now logged in
    console.log('User:', result.data.user);
} else {
    console.error('Login failed:', result.message);
}
```

### PHP/cURL - Apple Login

```php
$identityToken = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...'; // From Apple

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://yoursite.com/api/v1/auth/apple',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'identityToken' => $identityToken,
        'guard' => 'customer'
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_RETURNTRANSFER => true,
]);

$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

if (!$result['error']) {
    $apiToken = $result['data']['token'];
    $user = $result['data']['user'];
    // Use the API token for authenticated requests
}
```

### PHP/cURL - Google Login

```php
$identityToken = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...'; // From Google

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://yoursite.com/api/v1/auth/google',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'identityToken' => $identityToken,
        'guard' => 'customer'
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_RETURNTRANSFER => true,
]);

$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

if (!$result['error']) {
    $apiToken = $result['data']['token'];
    $user = $result['data']['user'];
    // Use the API token for authenticated requests
}
```

### JavaScript - Facebook Login

```javascript
// After getting access token from Facebook Login
const response = await fetch('/api/v1/auth/facebook', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    body: JSON.stringify({
        accessToken: facebookAccessToken, // From Facebook's response
        guard: 'web'
    })
});

const result = await response.json();

if (!result.error) {
    // Store the API token for future requests
    localStorage.setItem('api_token', result.data.token);

    // User is now logged in
    console.log('User:', result.data.user);
} else {
    console.error('Login failed:', result.message);
}
```

### PHP/cURL - Facebook Login

```php
$accessToken = 'EAABwzLixnjYBO...'; // From Facebook

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://yoursite.com/api/v1/auth/facebook',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'accessToken' => $accessToken,
        'guard' => 'web'
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_RETURNTRANSFER => true,
]);

$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

if (!$result['error']) {
    $apiToken = $result['data']['token'];
    $user = $result['data']['user'];
    // Use the API token for authenticated requests
}
```

### JavaScript - X (Twitter) Login

```javascript
// After getting access token from X OAuth
const response = await fetch('/api/v1/auth/x', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    body: JSON.stringify({
        accessToken: xAccessToken, // From X's OAuth response
        guard: 'web'
    })
});

const result = await response.json();

if (!result.error) {
    // Store the API token for future requests
    localStorage.setItem('api_token', result.data.token);

    // User is now logged in
    console.log('User:', result.data.user);
} else {
    console.error('Login failed:', result.message);
}
```

### PHP/cURL - X (Twitter) Login

```php
$accessToken = 'AAAAAAAAAAAAAAAAAAAAAMLheAAAAAAA0%2BuSeid...'; // From X OAuth

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://yoursite.com/api/v1/auth/x',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'accessToken' => $accessToken,
        'guard' => 'web'
    ]),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_RETURNTRANSFER => true,
]);

$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

if (!$result['error']) {
    $apiToken = $result['data']['token'];
    $user = $result['data']['user'];
    // Use the API token for authenticated requests
}
```

## Error Codes

- `400`: Invalid token or verification failed
- `422`: Validation error (missing required fields)
- `500`: Internal server error

## Configuration

### Facebook Settings
Configure Facebook authentication in the admin panel under Social Login settings:
- **Facebook App ID**: Your Facebook App ID from Facebook Developer Console
- **Facebook App Secret**: Your Facebook App Secret

### Google Settings
Configure Google authentication in the admin panel under Social Login settings:
- **Google Client ID**: Your Google OAuth 2.0 client ID from Google Developer Console
- **Google Client Secret**: Your Google OAuth 2.0 client secret

### Apple Settings
Configure Apple authentication in the admin panel under Social Login settings:
- **Apple App ID**: Your Apple Services ID
- **Apple App Secret**: Your Apple private key or client secret

### X (Twitter) Settings
Configure X authentication in the admin panel under Social Login settings:
- **X Client ID**: Your X OAuth 2.0 client ID from X Developer Portal
- **X Client Secret**: Your X OAuth 2.0 client secret

## Notes

- The API uses Laravel Sanctum for token generation
- All social login tokens (Apple, Google, Facebook, X) are verified for authenticity
- Users are automatically created if they don't exist
- Social login records are maintained for account linking
- The implementation follows each provider's token verification guidelines
- All endpoints support 'web' and 'customer' guards (if ecommerce plugin is active)
