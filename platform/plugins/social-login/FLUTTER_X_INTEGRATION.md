# Flutter X (Twitter) Login Integration Guide

This guide shows how to integrate X (Twitter) login with your Flutter app using the Botble CMS Social Login API.

## Prerequisites

1. X Developer Account with OAuth 2.0 app configured
2. Flutter app with HTTP client setup
3. Botble CMS with Social Login plugin and X provider enabled

## Step 1: Add Dependencies

Add these dependencies to your `pubspec.yaml`:

```yaml
dependencies:
  flutter:
    sdk: flutter
  http: ^1.1.0
  twitter_login: ^4.4.2  # For X OAuth
  shared_preferences: ^2.2.2  # For token storage
```

## Step 2: Configure X OAuth

### Android Configuration

Add to `android/app/src/main/AndroidManifest.xml`:

```xml
<activity
    android:name="com.example.app.MainActivity"
    android:exported="true"
    android:launchMode="singleTop"
    android:theme="@style/LaunchTheme">
    
    <!-- Add this intent filter for X callback -->
    <intent-filter android:autoVerify="true">
        <action android:name="android.intent.action.VIEW" />
        <category android:name="android.intent.category.DEFAULT" />
        <category android:name="android.intent.category.BROWSABLE" />
        <data android:scheme="https"
              android:host="yourapp.com"
              android:pathPrefix="/auth/callback" />
    </intent-filter>
</activity>
```

### iOS Configuration

Add to `ios/Runner/Info.plist`:

```xml
<key>CFBundleURLTypes</key>
<array>
    <dict>
        <key>CFBundleURLName</key>
        <string>X OAuth</string>
        <key>CFBundleURLSchemes</key>
        <array>
            <string>yourapp</string>
        </array>
    </dict>
</array>
```

## Step 3: Implement X Login Service

Create `lib/services/x_login_service.dart`:

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:twitter_login/twitter_login.dart';
import 'package:shared_preferences/shared_preferences.dart';

class XLoginService {
  static const String baseUrl = 'https://yoursite.com';
  static const String apiKey = 'your-x-api-key';
  static const String apiSecret = 'your-x-api-secret';
  static const String redirectUri = 'https://yourapp.com/auth/callback';

  Future<Map<String, dynamic>?> signInWithX() async {
    try {
      // Initialize X Login
      final twitterLogin = TwitterLogin(
        apiKey: apiKey,
        apiSecretKey: apiSecret,
        redirectURI: redirectUri,
      );

      // Perform X OAuth
      final authResult = await twitterLogin.login();

      if (authResult.status == TwitterLoginStatus.loggedIn) {
        // Get access token
        final accessToken = authResult.authToken;
        
        if (accessToken != null) {
          // Send token to Botble API
          return await _verifyWithBotble(accessToken);
        }
      } else if (authResult.status == TwitterLoginStatus.cancelledByUser) {
        throw Exception('Login cancelled by user');
      } else {
        throw Exception('Login failed: ${authResult.errorMessage}');
      }
    } catch (e) {
      print('X login error: $e');
      throw Exception('X login failed: $e');
    }
    
    return null;
  }

  Future<Map<String, dynamic>> _verifyWithBotble(String accessToken) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api/v1/auth/x'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'accessToken': accessToken,
          'guard': 'web', // or 'customer' for ecommerce
        }),
      );

      final responseData = jsonDecode(response.body);

      if (response.statusCode == 200 && !responseData['error']) {
        // Save API token
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('api_token', responseData['data']['token']);
        
        return responseData['data'];
      } else {
        throw Exception(responseData['message'] ?? 'Login verification failed');
      }
    } catch (e) {
      print('Botble API error: $e');
      throw Exception('Failed to verify login with server: $e');
    }
  }

  Future<void> signOut() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('api_token');
  }

  Future<String?> getStoredToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('api_token');
  }

  Future<bool> isLoggedIn() async {
    final token = await getStoredToken();
    return token != null && token.isNotEmpty;
  }
}
```

## Step 4: Create Login Widget

Create `lib/widgets/x_login_button.dart`:

```dart
import 'package:flutter/material.dart';
import '../services/x_login_service.dart';

class XLoginButton extends StatefulWidget {
  final VoidCallback? onSuccess;
  final Function(String)? onError;

  const XLoginButton({
    Key? key,
    this.onSuccess,
    this.onError,
  }) : super(key: key);

  @override
  State<XLoginButton> createState() => _XLoginButtonState();
}

class _XLoginButtonState extends State<XLoginButton> {
  final XLoginService _xLoginService = XLoginService();
  bool _isLoading = false;

  Future<void> _handleXLogin() async {
    setState(() {
      _isLoading = true;
    });

    try {
      final result = await _xLoginService.signInWithX();
      
      if (result != null) {
        widget.onSuccess?.call();
        
        // Show success message
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Successfully logged in with X!'),
              backgroundColor: Colors.green,
            ),
          );
        }
      }
    } catch (e) {
      widget.onError?.call(e.toString());
      
      // Show error message
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('X login failed: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      height: 50,
      child: ElevatedButton.icon(
        onPressed: _isLoading ? null : _handleXLogin,
        icon: _isLoading
            ? const SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(strokeWidth: 2),
              )
            : const Icon(Icons.close, color: Colors.white), // X icon
        label: Text(
          _isLoading ? 'Signing in...' : 'Continue with X',
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w600,
            color: Colors.white,
          ),
        ),
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.black, // X brand color
          foregroundColor: Colors.white,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(8),
          ),
        ),
      ),
    );
  }
}
```

## Step 5: Use in Your App

In your login screen:

```dart
import 'package:flutter/material.dart';
import 'widgets/x_login_button.dart';
import 'services/x_login_service.dart';

class LoginScreen extends StatefulWidget {
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final XLoginService _xLoginService = XLoginService();

  @override
  void initState() {
    super.initState();
    _checkLoginStatus();
  }

  Future<void> _checkLoginStatus() async {
    final isLoggedIn = await _xLoginService.isLoggedIn();
    if (isLoggedIn) {
      // Navigate to home screen
      Navigator.pushReplacementNamed(context, '/home');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Login')),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Text(
              'Welcome Back!',
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 32),
            
            // X Login Button
            XLoginButton(
              onSuccess: () {
                Navigator.pushReplacementNamed(context, '/home');
              },
              onError: (error) {
                print('Login error: $error');
              },
            ),
            
            const SizedBox(height: 16),
            
            // Other login options...
          ],
        ),
      ),
    );
  }
}
```

## Step 6: Handle API Requests

Create an HTTP client that includes the auth token:

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'services/x_login_service.dart';

class ApiClient {
  static const String baseUrl = 'https://yoursite.com';
  final XLoginService _authService = XLoginService();

  Future<Map<String, String>> _getHeaders() async {
    final token = await _authService.getStoredToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  Future<http.Response> get(String endpoint) async {
    final headers = await _getHeaders();
    return http.get(Uri.parse('$baseUrl$endpoint'), headers: headers);
  }

  Future<http.Response> post(String endpoint, Map<String, dynamic> data) async {
    final headers = await _getHeaders();
    return http.post(
      Uri.parse('$baseUrl$endpoint'),
      headers: headers,
      body: jsonEncode(data),
    );
  }
}
```

## Troubleshooting

### Common Issues

1. **OAuth Callback Not Working**
   - Verify redirect URI matches exactly in X Developer Portal
   - Check URL scheme configuration in iOS/Android

2. **Token Verification Failed**
   - Ensure X app has proper permissions (read users, read email)
   - Check API keys are correct in Flutter app

3. **Network Errors**
   - Verify Botble CMS URL is accessible
   - Check CORS settings if needed

### Debug Tips

1. Enable debug logging:
```dart
// Add to main.dart
import 'dart:developer' as developer;

void main() {
  // Enable HTTP logging
  developer.log('Starting app with debug mode');
  runApp(MyApp());
}
```

2. Check API response:
```dart
print('API Response: ${response.body}');
print('Status Code: ${response.statusCode}');
```

## Security Notes

- Store API keys securely (consider using flutter_dotenv)
- Validate SSL certificates in production
- Implement token refresh logic if needed
- Use secure storage for sensitive data

## Next Steps

1. Implement logout functionality
2. Add error handling for network issues
3. Implement token refresh if your API supports it
4. Add loading states and better UX
5. Test on both iOS and Android devices
