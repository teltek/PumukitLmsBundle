# PumukitLmsBundle LTI

## Introduction

This bundle provides an LTI (Learning Tools Interoperability) integration for PuMuKIT. It allows to connect PuMuKIT with any LTI compatible platform.

## Configuration

You need to generate private and public key RSA256 and store it on '__PROJECT_DIR__/config/lti/keys/private.pem'

## Registration

The automatic registration (`/lti/register`) only reads `https` addresses of a domain of `pumukit_lms.domains` or of a subdomain of it, without
following redirects, and it checks the `issuer` and the `registration_endpoint` that it receives in the same way. With `*` in `domains`, it does not reach internal addresses.

To register a new LTI tool, you need to create a new LTI tool in the LMS admin panel. PuMuKIT provides an auto register endpoint to register the LTI tool in the LMS  `{naked-domain}/lti/register`.

{naked-domain} is the domain of your PuMuKIT single backoffice defined on naked_backoffice_domain parameter in parameters.yml.

## Usage

Once the LTI tool is registered and active, you can use the LTI tool in the LMS platform adding the External Tool generated where you want to use it.

## Security

The launches are validated: the client is found by the issuer and the audience of the token, the token is checked
with the keys of the platform (`jwks_uri` of its OpenID configuration), and its audience, nonce, deployment and LTI
version must match the ones of the login, which is tied to the launch with a signed state that can be used once.

Only the users with an LTI instructor, content developer or administrator role are created or promoted in PuMuKIT.
The learners watch the embedded videos without becoming users, unless `lti_learner_profile` is set.

## Personal recorder

The LMS iframes do not allow the screen capture (Moodle does not add `display-capture` to them), so the naked
back-office opened from LTI shows a "Record" button that opens the personal recorder in a new tab, logged in with a
link valid five minutes that can be used once.

## Important

This LTI integration is based on the LTI 1.3 and use deep linking to connect the LMS platform with PuMuKIT.

Its necessary LTI Advantage to use this integration.

## FAQ

### ERROR: Consumer key is incorrect

PuMuKIT saves de LTIClient info in the database when LMS register the tool. If you have this error, you can remove the LTIClient info from the database and register the tool again.