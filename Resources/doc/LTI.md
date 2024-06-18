# PumukitLmsBundle LTI

## Introduction

This bundle provides an LTI (Learning Tools Interoperability) integration for PuMuKIT. It allows to connect PuMuKIT with any LTI compatible platform.

## Configuration

You need to generate private and public key RSA256 and store it on '__PROJECT_DIR__/config/lti/keys/private.pem'

## Registration

To register a new LTI tool, you need to create a new LTI tool in the LMS admin panel. PuMuKIT provides an auto register endpoint to register the LTI tool in the LMS  `{naked-domain}/lti/register`.

{naked-domain} is the domain of your PuMuKIT single backoffice defined on naked_backoffice_domain parameter in parameters.yml.

## Usage

Once the LTI tool is registered and active, you can use the LTI tool in the LMS platform adding the External Tool generated where you want to use it.

## Important

This LTI integration is based on the LTI 1.3 and use deep linking to connect the LMS platform with PuMuKIT.

Its necessary LTI Advantage to use this integration.