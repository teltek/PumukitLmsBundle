<?php

namespace Pumukit\LmsBundle\Controller;

use FOS\UserBundle\Model\UserInterface;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * @Route("/sso")
 */
class SSOController extends Controller
{
    protected function getAndValidateUser(string $email, string $username, string $host, string $hash, bool $isSecure)
    {
        if (!$this->container->hasParameter('pumukit.naked_backoffice_domain')) {
            return $this->genError('The domain "pumukit.naked_backoffice_domain" is not configured.');
        }

        if ($username) {
            $type = 'username';
            $value = $username;
        } elseif (!empty($email)) {
            $type = 'email';
            $value = $email;
        } else {
            return $this->genError('Not email or username parameter.');
        }

        $logger = $this->container->get('logger');

        $lmsService = $this->container->get('pumukit_lms.lms');
        if (!$lmsService->validateAccessDomain($host)) {
            return $this->genError('Invalid Domain!');
        }

        $ssoService = $this->container->get('pumukit_lms.sso');
        $logger->info('TTK Validate hash: '.$ssoService->validateHash($hash, $value));
        if (!$ssoService->validateHash($hash, $value)) {
            return $this->genError('The hash is not valid.');
        }

        // Only HTTPs
        if (!$isSecure) {
            return $this->genError('Only HTTPS connections are allowed.');
        }

        $repo = $this
            ->get('doctrine_mongodb.odm.document_manager')
            ->getRepository(User::class)
        ;

        // Find User
        try {
            $user = null;
            if ($username) {
                $logger->info('TTK FindByUsername: '.$username);
                $user = $repo->findOneBy(['username' => $username]);
            }
            if (!$user && $email) {
                $logger->info('TTK Not found by username, FindByEmail: '.$email);
                $user = $repo->findOneBy(['email' => $email]);
            }
            if (!$user) {
                $logger->info('TTK Not found user, create user: '.$type.' - '.$value);
                $user = $ssoService->createUser([$type => $value]);
            } else {
                $logger->info('TTK update user');
                $ssoService->promoteUser($user);
            }
        } catch (\RuntimeException $e) {
            $logger->info('TTK Runtime exception');
        } catch (\Exception $e) {
            if ($this->getParameter('pumukit_lms.allow_create_users_from_req') && $email && $username) {
                $logger->info('TTK Exception, now create user by username and email: '.$username.' - '.$email);

                return $ssoService->createUserByUsernameAndEmail($username, $email, $username);
            }

            return $this->genError($e->getMessage());
        }

        return $user;
    }

    protected function login(UserInterface $user, Request $request): void
    {
        $token = new UsernamePasswordToken($user, $user->getPassword(), 'public', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);
        $event = new InteractiveLoginEvent($request, $token);
        $this->get('event_dispatcher')->dispatch('security.interactive_login', $event);
    }

    protected function genError(string $message = 'Not Found', int $status = 404)
    {
        return new Response(
            $this->renderView('PumukitLmsBundle:SSO:error.html.twig', ['message' => $message]),
            $status
        );
    }

    protected function serializeObject($object, string $lang, string $format = 'json')
    {
        $serializer = $this->get('serializer');
        $data = $serializer->serialize($object, $format);

        return new Response($data);
    }

    protected function buildI18nTitle(string $titleParam, array $locales)
    {
        $title = [];
        foreach ($locales as $locale) {
            $title[$locale] = $titleParam;
        }

        return $title;
    }

    protected function getSeries($i18nTitle)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $repo = $dm->getRepository(Series::class);

        return $repo->findOneBy(['title' => $i18nTitle]);
    }

    protected function buildParams($i18nTitle, $seriesId)
    {
        $data = [];
        $data['mmobjData'] = [];
        $data['mmobjData']['properties'] = [
            'lms' => true,
        ];
        if ($i18nTitle) {
            $data['seriesData'] = [];
            $data['seriesData']['title'] = $i18nTitle;
        }
        $values = ['externalData' => $data];

        if ($seriesId) {
            $values['series'] = $seriesId;
        }

        return http_build_query($values);
    }
}
