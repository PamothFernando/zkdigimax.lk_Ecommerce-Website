# X (Twitter) Login Setup Guide

This guide will help you set up X (formerly Twitter) login for your Botble CMS application.

## Prerequisites

1. A X (Twitter) Developer Account
2. Botble CMS with Social Login plugin installed

## Step 1: Create a X Developer App

1. Go to [X Developer Portal](https://developer.x.com/en/portal/dashboard)
2. Sign in with your X account
3. Click "Create App" or "New Project"
4. Fill in the required information:
   - App name
   - App description
   - Website URL
   - Terms of Service URL
   - Privacy Policy URL

## Step 2: Configure OAuth Settings

1. In your X app dashboard, go to "Settings" > "User authentication settings"
2. Enable "OAuth 2.0"
3. Set the callback URL to: `https://yourdomain.com/auth/callback/x`
   - Replace `yourdomain.com` with your actual domain
4. Set the website URL to your site's homepage
5. Save the settings

## Step 3: Get Your Credentials

1. Go to "Keys and tokens" tab
2. Copy the "Client ID" and "Client Secret"
3. Keep these credentials secure

## Step 4: Configure Botble CMS

1. Log in to your Botble CMS admin panel
2. Go to "Settings" > "Social Login"
3. Enable "Social Login" if not already enabled
4. Find the "X (Twitter)" section
5. Enable "Enable X (Twitter) login"
6. Enter your Client ID and Client Secret
7. Save the settings

## Step 5: Test the Integration

1. Go to your site's login page
2. You should see an "X (Twitter)" login button
3. Click it to test the OAuth flow
4. Authorize your app on X
5. You should be redirected back and logged in

## Troubleshooting

### Common Issues

1. **Invalid redirect URI**: Make sure the callback URL in X matches exactly: `https://yourdomain.com/auth/callback/x`
2. **App not approved**: Some X apps require approval for production use
3. **Rate limiting**: X has rate limits on API calls

### Required X App Permissions

Make sure your X app has the following permissions:
- Read users
- Read email addresses (if you want to get user emails)

## Security Notes

- Keep your Client Secret secure and never expose it in client-side code
- Use HTTPS in production
- Regularly rotate your credentials
- Monitor your app's usage in the X Developer Portal

## Support

If you encounter issues:
1. Check the Botble CMS logs
2. Verify your X app configuration
3. Ensure your callback URL is correct
4. Contact Botble support if needed
