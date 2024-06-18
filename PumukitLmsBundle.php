<?php

declare(strict_types=1);

namespace Pumukit\LmsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PumukitLmsBundle extends Bundle
{
    public const PROPERTY_LMS = 'lms';
    public const LMS_TAG_CODE = 'PUCHLMS';
    public const LTI_TOOL_REGISTER_URL = '/lti/register';
    public const LTI_TOOL_LOGIN_URL = '/lti/login';
    public const LTI_TOOL_LAUNCH_URL = '/lti/launch';
    public const LTI_TOOL_FAVICON_URL = '/images/favicon.png';
    public const LTI_TOOL_PUBLIC_KEYSET_URL = '/.well-known/lti/jwks.json';
}
