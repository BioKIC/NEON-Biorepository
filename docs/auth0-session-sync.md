# Auth0 session synchronization

The NEON site and the Biorepository keep separate application sessions. They
share the Auth0 session through the same issuer hostname.

## Runtime flow

- Interactive sign-in uses the authorization-code flow without `prompt=login`.
  An existing Auth0 session therefore completes without another credential
  prompt.
- The Biorepository performs a hidden `prompt=none` check at most once per
  minute. A successful check creates the local Symbiota/PHP session. A
  `login_required` response clears a stale local session.
- Interactive sign-out clears the local session before redirecting through the
  Auth0 `/v2/logout` endpoint.
- `profile/logout.php` also accepts and verifies an OIDC back-channel
  `logout_token`, then invalidates the PHP session associated with its `sid`.

## Auth0 application settings

Keep the existing callback and logout URLs registered for the production host:

- Allowed callback URL:
  `https://biorepo.neonscience.org/profile/authCallback.php`
- Allowed logout URL:
  `https://biorepo.neonscience.org/profile/logout.php`
- OIDC back-channel logout URI:
  `https://biorepo.neonscience.org/profile/logout.php`

Configure back-channel logout to include the session ID (`sid`). The local
`usersthirdpartysessions` table maps that claim to the PHP session to revoke.

If the main site and Biorepository continue to share one Auth0 client, remember
that the client has one back-channel logout configuration. The main site must
either detect the cleared Auth0 session itself, forward logout events, or the
two backends should use separate clients in the same tenant. Separate clients
still share tenant SSO.

## Smoke tests

1. Log in to the main NEON site, then open the Biorepository. The page should
   refresh once and show the local account as signed in.
2. Start signed out everywhere and open the Biorepository. It should remain
   anonymous without displaying Universal Login.
3. Click Biorepository **Sign In** while signed out. Universal Login should be
   displayed; when an Auth0 session already exists, it should be skipped.
4. Click Biorepository **Sign Out**. The local session should end even if the
   Auth0 redirect is interrupted.
5. Log out from the main site. With back-channel logout configured, the mapped
   Biorepository PHP session should be invalidated.

