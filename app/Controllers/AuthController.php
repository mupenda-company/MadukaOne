<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Models/User.php';

final class AuthController
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function login(array $params = []): void
    {
        $this->sendNoStoreHeaders();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $view = dirname(__DIR__) . '/Views/auth/login.php';

            if (is_file($view)) {
                require $view;
                return;
            }

            echo 'Login';
            return;
        }

        $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $password = (string) ($_POST['password'] ?? '');

        if ($email === false || $password === '') {
            $this->flash('Email ou mot de passe invalide.');
            $this->redirect('/login');
        }

        $user = $this->findUserByEmail($email);

        if (
            $user === null
            || empty($user['password_hash'])
            || !$this->users->verifyPassword($user, $password)
        ) {
            $this->flash('Identifiants incorrects.');
            $this->redirect('/login');
        }

        $this->startAuthenticatedSession($user);
        $this->touchLastLogin((int) $user['id']);
        $this->redirectAfterLogin($user);
    }

    public function authenticate(array $params = []): void
    {
        $this->login($params);
    }

    public function googleCallback(array $params = []): void
    {
        $this->handleSocialCallback(
            provider: 'google',
            tokenEndpoint: 'https://oauth2.googleapis.com/token',
            clientIdEnv: 'GOOGLE_CLIENT_ID',
            clientSecretEnv: 'GOOGLE_CLIENT_SECRET',
            redirectUriEnv: 'GOOGLE_REDIRECT_URI'
        );
    }

    public function appleCallback(array $params = []): void
    {
        $this->handleSocialCallback(
            provider: 'apple',
            tokenEndpoint: 'https://appleid.apple.com/auth/token',
            clientIdEnv: 'APPLE_CLIENT_ID',
            clientSecretEnv: 'APPLE_CLIENT_SECRET',
            redirectUriEnv: 'APPLE_REDIRECT_URI'
        );
    }

    public function logout(array $params = []): void
    {
        $this->startSession();
        $this->sendNoStoreHeaders();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        session_destroy();
        $this->redirect('/login');
    }

    public function redirectToGoogle(array $params = []): void
    {
        $this->redirectToOAuthProvider(
            authorizationUrl: 'https://accounts.google.com/o/oauth2/v2/auth',
            clientIdEnv: 'GOOGLE_CLIENT_ID',
            redirectUriEnv: 'GOOGLE_REDIRECT_URI',
            scope: 'openid email profile'
        );
    }

    public function redirectToApple(array $params = []): void
    {
        $this->redirectToOAuthProvider(
            authorizationUrl: 'https://appleid.apple.com/auth/authorize',
            clientIdEnv: 'APPLE_CLIENT_ID',
            redirectUriEnv: 'APPLE_REDIRECT_URI',
            scope: 'openid email name',
            extra: ['response_mode' => 'form_post']
        );
    }

    public function activateAccount(array $params = []): void
    {
        try {
            $this->sendNoStoreHeaders();
            $this->startSession();

            $code = trim((string) ($_POST['invitation_code'] ?? $_POST['code'] ?? ''));

            if ($code === '') {
                $this->flash('Veuillez saisir votre code d invitation.');
                $this->redirect('/login');
            }

            $user = $this->users->findPendingActivationByCode($code);

            if ($user === null) {
                $this->flash('Code d invitation invalide ou deja utilise.');
                $this->redirect('/login');
            }

            $_SESSION['pending_activation_id'] = (int) $user['id'];

            $this->redirectToOAuthProvider(
                authorizationUrl: 'https://accounts.google.com/o/oauth2/v2/auth',
                clientIdEnv: 'GOOGLE_CLIENT_ID',
                redirectUriEnv: 'GOOGLE_REDIRECT_URI',
                scope: 'openid email profile'
            );
        } catch (Throwable $exception) {
            unset($_SESSION['pending_activation_id']);
            $this->flash('Activation impossible. Veuillez verifier votre code.');
            $this->redirect('/login');
        }
    }

    public function activate(array $params = []): void
    {
        $this->sendNoStoreHeaders();
        $this->startSession();

        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        require dirname(__DIR__) . '/Views/auth/activate.php';
    }

    public function activateWithCode(array $params = []): void
    {
        try {
            $this->sendNoStoreHeaders();
            $this->startSession();

            $code = trim((string) ($_POST['invitation_code'] ?? ''));

            if ($code === '') {
                $this->flash('Veuillez saisir votre code d invitation.');
                $this->redirect('/activate');
            }

            $user = $this->users->verifyInvitationCode($code);

            if ($user === null) {
                $this->flash('Code d invitation invalide ou deja utilise.');
                $this->redirect('/activate');
            }

            $_SESSION['pending_activation_id'] = (int) $user['id'];

            $this->redirectToOAuthProvider(
                authorizationUrl: 'https://accounts.google.com/o/oauth2/v2/auth',
                clientIdEnv: 'GOOGLE_CLIENT_ID',
                redirectUriEnv: 'GOOGLE_REDIRECT_URI',
                scope: 'openid email profile'
            );
        } catch (Throwable $exception) {
            unset($_SESSION['pending_activation_id']);
            $this->flash('Activation impossible. Veuillez reessayer.');
            $this->redirect('/activate');
        }
    }

    private function handleSocialCallback(
        string $provider,
        string $tokenEndpoint,
        string $clientIdEnv,
        string $clientSecretEnv,
        string $redirectUriEnv
    ): void {
        try {
            $this->sendNoStoreHeaders();
            $this->validateOAuthState();

            $code = trim((string) ($_GET['code'] ?? $_POST['code'] ?? ''));

            if ($code === '') {
                throw new RuntimeException('Code OAuth manquant.');
            }

            $idToken = $this->exchangeAuthorizationCode(
                tokenEndpoint: $tokenEndpoint,
                code: $code,
                clientId: $clientId = $this->requiredEnv($clientIdEnv),
                clientSecret: $this->requiredEnv($clientSecretEnv),
                redirectUri: $this->requiredEnv($redirectUriEnv)
            );
            $claims = $this->decodeJwtPayload($idToken);
            $audience = $claims['aud'] ?? null;

            if (is_array($audience) ? !in_array($clientId, $audience, true) : (string) $audience !== $clientId) {
                throw new RuntimeException('Audience OAuth invalide.');
            }

            $socialId = trim((string) ($claims['sub'] ?? ''));
            $email = filter_var(strtolower(trim((string) ($claims['email'] ?? ''))), FILTER_VALIDATE_EMAIL);

            if ($socialId === '') {
                throw new RuntimeException('Identifiant social manquant.');
            }

            if ($provider === 'google' && isset($_SESSION['pending_activation_id'])) {
                $pendingActivationId = (int) $_SESSION['pending_activation_id'];

                if ($pendingActivationId < 1 || $email === false) {
                    throw new RuntimeException('Activation Google incomplete.');
                }

                $user = $this->users->activateGoogleAccount($pendingActivationId, $email, $socialId);
                unset($_SESSION['pending_activation_id']);

                if ($user === null) {
                    throw new RuntimeException('Activation Google refusee.');
                }

                $this->startAuthenticatedSession($user);
                $this->touchLastLogin((int) $user['id']);
                $this->redirectAfterLogin($user);
            }

            $user = $this->users->findBySocialId($provider, $socialId);

            if ($user === null && $email !== false) {
                $user = $this->findUserByEmail($email);

                if ($user !== null) {
                    if (!$this->users->linkSocialAccount((int) $user['id'], $provider, $socialId)) {
                        throw new RuntimeException('Compte social deja lie.');
                    }

                    $user = $this->users->findById((int) $user['id']) ?? $user;
                }
            }

            if ($user === null) {
                $this->flash('Aucun compte associe a cette identite. Veuillez contacter votre administrateur.');
                $this->redirect('/login');
            }

            $this->startAuthenticatedSession($user);
            $this->touchLastLogin((int) $user['id']);
            $this->redirectAfterLogin($user);
        } catch (Throwable $exception) {
            unset($_SESSION['pending_activation_id']);
            $this->flash('Connexion ' . ucfirst($provider) . ' impossible. Veuillez reessayer.');
            $this->redirect('/login');
        }
    }

    private function findUserByEmail(string $email): ?array
    {
        return $this->users->findByEmail($email);
    }

    private function findOAuthUser(string $idColumn, string $providerId, string $email): ?array
    {
        return $this->users->findOAuthUser($idColumn, $providerId, $email);
    }

    private function startAuthenticatedSession(array $user): void
    {
        $this->startSession();
        session_regenerate_id(true);

        $_SESSION['user'] = $this->users->sessionPayload($user);
    }

    private function resolveRole(array $user): string
    {
        return $this->users->resolveRole($user);
    }

    private function redirectAfterLogin(array $user): never
    {
        $role = $this->resolveRole($user);
        $this->redirect($role === 'agent' ? '/pos' : '/dashboard');
    }

    private function touchLastLogin(int $userId): void
    {
        $this->users->touchLastLogin($userId);
    }

    private function linkProviderIfNeeded(int $userId, string $provider, string $idColumn, string $providerId, array $claims): void
    {
        $avatarUrl = isset($claims['picture']) && is_string($claims['picture']) ? $claims['picture'] : null;
        $this->users->linkProviderIfNeeded($userId, $provider, $idColumn, $providerId, $avatarUrl);
    }

    private function exchangeAuthorizationCode(
        string $tokenEndpoint,
        string $code,
        string $clientId,
        string $clientSecret,
        string $redirectUri
    ): string {
        $response = $this->postForm($tokenEndpoint, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
        ]);

        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data) || empty($data['id_token']) || !is_string($data['id_token'])) {
            throw new RuntimeException('Jeton OAuth id_token manquant.');
        }

        return $data['id_token'];
    }

    private function postForm(string $url, array $payload): string
    {
        $body = http_build_query($payload);

        if (function_exists('curl_init')) {
            $curl = curl_init($url);

            if ($curl === false) {
                throw new RuntimeException('Initialisation cURL impossible.');
            }

            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $response = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if (!is_string($response) || $response === '' || $status < 200 || $status >= 300) {
                throw new RuntimeException($error !== '' ? $error : 'Echange OAuth refuse par le fournisseur.');
            }

            return $response;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $body,
                'timeout' => 20,
            ],
        ]);

        $response = file_get_contents($url, false, $context);

        if (!is_string($response) || $response === '') {
            throw new RuntimeException('Echange OAuth impossible.');
        }

        return $response;
    }

    private function decodeJwtPayload(string $jwt): array
    {
        [$header64, $payload64, $signature64] = array_pad(explode('.', $jwt), 3, '');

        if ($header64 === '' || $payload64 === '' || $signature64 === '') {
            throw new RuntimeException('Format JWT invalide.');
        }

        $payload = json_decode($this->base64UrlDecode($payload64), true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($payload)) {
            throw new RuntimeException('Payload JWT invalide.');
        }

        if ((int) ($payload['exp'] ?? 0) < time()) {
            throw new RuntimeException('Jeton OAuth expire.');
        }

        return $payload;
    }

    private function exchangeCodeForIdToken(
        string $tokenEndpoint,
        string $code,
        string $clientId,
        string $clientSecret,
        string $redirectUri
    ): string {
        $payload = http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 10,
            ],
        ]);

        $response = file_get_contents($tokenEndpoint, false, $context);

        if ($response === false) {
            throw new RuntimeException('OAuth token exchange failed.');
        }

        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data) || empty($data['id_token']) || !is_string($data['id_token'])) {
            throw new RuntimeException('OAuth id_token missing.');
        }

        return $data['id_token'];
    }

    private function verifyIdToken(string $jwt, string $jwksUrl, array $issuers, string $audience): array
    {
        [$header64, $payload64, $signature64] = array_pad(explode('.', $jwt), 3, '');

        if ($header64 === '' || $payload64 === '' || $signature64 === '') {
            throw new RuntimeException('Invalid JWT format.');
        }

        $header = json_decode($this->base64UrlDecode($header64), true, 512, JSON_THROW_ON_ERROR);
        $claims = json_decode($this->base64UrlDecode($payload64), true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($header) || !is_array($claims) || ($header['alg'] ?? '') !== 'RS256') {
            throw new RuntimeException('Unsupported OAuth token.');
        }

        if (!in_array((string) ($claims['iss'] ?? ''), $issuers, true)) {
            throw new RuntimeException('Invalid token issuer.');
        }

        if ((string) ($claims['aud'] ?? '') !== $audience) {
            throw new RuntimeException('Invalid token audience.');
        }

        if ((int) ($claims['exp'] ?? 0) < time()) {
            throw new RuntimeException('Expired token.');
        }

        $pem = $this->publicKeyForKid($jwksUrl, (string) ($header['kid'] ?? ''));
        $verified = openssl_verify(
            $header64 . '.' . $payload64,
            $this->base64UrlDecode($signature64),
            $pem,
            OPENSSL_ALGO_SHA256
        );

        if ($verified !== 1) {
            throw new RuntimeException('Invalid token signature.');
        }

        return $claims;
    }

    private function publicKeyForKid(string $jwksUrl, string $kid): string
    {
        if ($kid === '') {
            throw new RuntimeException('Missing token key id.');
        }

        $response = file_get_contents($jwksUrl);

        if ($response === false) {
            throw new RuntimeException('Unable to fetch OAuth public keys.');
        }

        $jwks = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        foreach (($jwks['keys'] ?? []) as $key) {
            if (($key['kid'] ?? '') === $kid && ($key['kty'] ?? '') === 'RSA') {
                return $this->jwkToPem($key);
            }
        }

        throw new RuntimeException('OAuth public key not found.');
    }

    private function jwkToPem(array $jwk): string
    {
        $modulus = $this->base64UrlDecode((string) $jwk['n']);
        $exponent = $this->base64UrlDecode((string) $jwk['e']);
        $rsaPublicKey = $this->asn1Sequence($this->asn1Integer($modulus) . $this->asn1Integer($exponent));
        $algorithm = $this->asn1Sequence($this->asn1ObjectIdentifier('1.2.840.113549.1.1.1') . "\x05\x00");
        $publicKey = $this->asn1Sequence($algorithm . "\x03" . $this->asn1Length(strlen($rsaPublicKey) + 1) . "\x00" . $rsaPublicKey);

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($publicKey), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    private function asn1Integer(string $value): string
    {
        if (ord($value[0]) > 0x7f) {
            $value = "\x00" . $value;
        }

        return "\x02" . $this->asn1Length(strlen($value)) . $value;
    }

    private function asn1Sequence(string $value): string
    {
        return "\x30" . $this->asn1Length(strlen($value)) . $value;
    }

    private function asn1ObjectIdentifier(string $oid): string
    {
        $parts = array_map('intval', explode('.', $oid));
        $body = chr($parts[0] * 40 + $parts[1]);

        for ($i = 2, $count = count($parts); $i < $count; $i++) {
            $body .= $this->encodeOidPart($parts[$i]);
        }

        return "\x06" . $this->asn1Length(strlen($body)) . $body;
    }

    private function encodeOidPart(int $value): string
    {
        $result = chr($value & 0x7f);
        $value >>= 7;

        while ($value > 0) {
            $result = chr(($value & 0x7f) | 0x80) . $result;
            $value >>= 7;
        }

        return $result;
    }

    private function asn1Length(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $bytes = '';

        while ($length > 0) {
            $bytes = chr($length & 0xff) . $bytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    private function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);

        if ($decoded === false) {
            throw new RuntimeException('Invalid base64url value.');
        }

        return $decoded;
    }

    private function redirectToOAuthProvider(
        string $authorizationUrl,
        string $clientIdEnv,
        string $redirectUriEnv,
        string $scope,
        array $extra = []
    ): void {
        try {
            $this->startSession();
            $state = bin2hex(random_bytes(32));
            $_SESSION['oauth_state'] = $state;

            $query = http_build_query(array_merge([
                'client_id' => $this->requiredEnv($clientIdEnv),
                'redirect_uri' => $this->requiredEnv($redirectUriEnv),
                'response_type' => 'code',
                'scope' => $scope,
                'state' => $state,
            ], $extra));

            $this->redirect($authorizationUrl . '?' . $query);
        } catch (Throwable $exception) {
            unset($_SESSION['oauth_state']);
            unset($_SESSION['pending_activation_id']);
            $this->flash('Connexion sociale indisponible : configuration OAuth incomplete.');
            $this->redirect('/login');
        }
    }

    private function validateOAuthState(): void
    {
        $this->startSession();

        $expected = $_SESSION['oauth_state'] ?? null;
        $received = $_POST['state'] ?? $_GET['state'] ?? null;
        unset($_SESSION['oauth_state']);

        if (!is_string($expected) || !is_string($received) || !hash_equals($expected, $received)) {
            throw new RuntimeException('Invalid OAuth state.');
        }
    }

    private function requiredEnv(string $key): string
    {
        $value = getenv($key);

        if (!is_string($value) || trim($value) === '') {
            throw new RuntimeException("Missing environment variable {$key}.");
        }

        return trim($value);
    }

    private function flash(string $message): void
    {
        $this->startSession();
        $_SESSION['flash_error'] = $message;
    }

    private function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
            ]);
        }
    }

    private function sendNoStoreHeaders(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    }

    private function redirect(string $path): never
    {
        if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://') && !str_starts_with($path, '//')) {
            $basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
            $basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;
            $path = $basePath . '/' . ltrim($path, '/');
        }

        header('Location: ' . $path, true, 302);
        exit;
    }
}
