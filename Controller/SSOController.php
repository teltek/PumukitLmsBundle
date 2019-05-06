<?php

namespace Pumukit\LmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Pumukit\SchemaBundle\Document\User;

/**
 * @Route("/sso")
 */
class SSOController extends Controller
{
    /**
     * @param $email
     * @param $username
     * @param $host
     * @param $hash
     * @param $isSecure
     *
     * @return Response|null
     */
    protected function getAndValidateUser($email, $username, $host, $hash, $isSecure)
    {
        if (!$this->container->hasParameter('pumukit.naked_backoffice_domain')) {
            return $this->genError('The domain "pumukit.naked_backoffice_domain" is not configured.');
        }

        if ($username) {
            $type = 'username';
            $value = $username;
        } elseif ($email) {
            $type = 'email';
            $value = $email;
        } else {
            return $this->genError('Not email or username parameter.');
        }

        $lmsService = $this->container->get('pumukit_lms.lms');
        if (!$lmsService->validateAccessDomain($host)) {
            return $this->genError('Invalid Domain!');
        }

        $ssoService = $this->container->get('pumukit_lms.sso');
        if (!$ssoService->validateHash($hash, $value)) {
            return $this->genError('The hash is not valid.');
        }

        //Only HTTPs
        if (!$isSecure) {
            return $this->genError('Only HTTPS connections are allowed.');
        }

        $repo = $this
            ->get('doctrine_mongodb.odm.document_manager')
            ->getRepository('PumukitSchemaBundle:User');

        //Find User
        try {
            $user = null;
            if ($username) {
                $user = $repo->findOneBy(array('username' => $username));
            }
            if (!$user && $email) {
                $user = $repo->findOneBy(array('email' => $email));
            }
            if (!$user) {
                $user = $ssoService->createUser(array($type => $value));
            } else {
                $ssoService->promoteUser($user);
            }
        } catch (\Exception $e) {
            if ($this->getParameter('pumukit_lms.allow_create_users_from_req') && $email && $username) {
                return $ssoService->createUserWithInfo($username, $email);
            }

            return $this->genError($e->getMessage());
        }

        return $user;
    }

    /**
     * @param         $user
     * @param Request $request
     */
    protected function login($user, Request $request)
    {
        $token = new UsernamePasswordToken($user, $user->getPassword(), 'public', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);
        $event = new InteractiveLoginEvent($request, $token);
        $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);
    }

    /**
     * @param string $message
     * @param int    $status
     *
     * @return Response
     */
    protected function genError($message = 'Not Found', $status = 404)
    {
        return new Response(
            $this->renderView('PumukitLmsBundle:SSO:error.html.twig', array('message' => $message)),
            $status
        );
    }

    /**
     * @param        $object
     * @param        $lang
     * @param string $format
     *
     * @return Response
     */
    protected function serializeObject($object, $lang, $format = 'json')
    {
        $serializer = $this->get('serializer');
        $data = $serializer->serialize($object, $format);

        return new Response($data);
    }

    /**
     * @param $titleParam
     * @param $locales
     *
     * @return array
     */
    protected function buildI18nTitle($titleParam, $locales)
    {
        $title = array();
        foreach ($locales as $locale) {
            $title[$locale] = $titleParam;
        }

        return $title;
    }

    /**
     * @param $i18nTitle
     *
     * @return mixed
     */
    protected function getSeries($i18nTitle)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $repo = $dm->getRepository('PumukitSchemaBundle:Series');

        return $repo->findOneBy(array('title' => $i18nTitle));
    }

    /**
     * @param $i18nTitle
     * @param $seriesId
     *
     * @return string
     */
    protected function buildParams($i18nTitle, $seriesId)
    {
        $data = array();
        $data['mmobjData'] = array();
        $data['mmobjData']['properties'] = array('openedx' => true);
        if ($i18nTitle) {
            $data['seriesData'] = array();
            $data['seriesData']['title'] = $i18nTitle;
        }
        $values = array('externalData' => $data);

        if ($seriesId) {
            $values['series'] = $seriesId;
        }

        $params = http_build_query($values);

        return $params;
    }
}
