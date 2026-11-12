# PumukitLmsBundle LTI

## Introduction

This bundle provides an LTI (Learning Tools Interoperability) integration for PuMuKIT. It connects PuMuKIT with any LTI compatible platform.

## Configuration

Generate an RSA private key in `config/lti/keys/private.pem` of the PuMuKIT project. The tool signs its messages
with it (RS256) and publishes the public key in `/.well-known/lti/jwks.json`:

```bash
mkdir -p config/lti/keys
openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:2048 -out config/lti/keys/private.pem
```

## Registration

The automatic registration (`/lti/register`) only reads `https` addresses of a domain of `pumukit_lms.domains` or of a subdomain of it, without
following redirects, and it checks the `issuer` and the `registration_endpoint` that it receives in the same way. With `*` in `domains`, it does not reach internal addresses.

To register a new LTI tool, create a new LTI tool in the LMS admin panel. PuMuKIT provides an automatic registration endpoint for the LMS: `https://{naked-domain}/lti/register`.

`{naked-domain}` is the domain of the naked back-office, set in the `pumukit_lms.naked_backoffice_domain` option.

## Usage

Once the LTI tool is registered and active, add the generated External Tool to the LMS where you want to use it.

## Security

The launches are validated: the client is found by the issuer and the audience of the token, the token is checked
with the keys of the platform (`jwks_uri` of its OpenID configuration), and its audience, nonce, deployment and LTI
version must match the ones of the login, which is tied to the launch with a signed state that can be used once.

Only the users with an LTI instructor, content developer or administrator role are created or promoted in PuMuKIT.
The learners watch the embedded videos without becoming users, unless `lti_learner_profile` is set.

## Personal recorder

The LMS iframes do not allow the screen capture (Moodle does not add `display-capture` to them), so the naked
back-office opened from LTI shows a "Record" button that opens the personal recorder in a new tab, logged in with a
link valid five minutes that can be used once. The button is only shown when the PumukitPersonalRecorderBundle is
installed.

## Important

This LTI integration is based on LTI 1.3 and uses deep linking to connect the LMS platform with PuMuKIT.

The LMS must support LTI Advantage to use this integration.

## FAQ

### ERROR: Consumer key is incorrect

PuMuKIT saves the LTI client (the `LTIClient` document) in the database when the LMS registers the tool. If you get
this error, remove that LTI client from the database and register the tool again.
