<?php

namespace SeteMares\Apple;

use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\ProviderInterface;

class SocialiteProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'name',
        'email',
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected $encodingType = PHP_QUERY_RFC3986;

    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://appleid.apple.com/auth/authorize',
            $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'scope' => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'response_type' => 'code',
            'response_mode' => 'form_post',
        ];

        if ($this->usesState()) {
            $fields['state'] = $state;
        }

        return array_merge($fields, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://appleid.apple.com/auth/token';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()
            ->post(
                $this->getTokenUrl(),
                [
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode(
                            $this->clientId . ':' . $this->clientSecret
                        ),
                    ],
                    'body' => $this->getTokenFields($code),
                ]
            );

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessToken($response)
    {
        $data = $response->json();

        return $data['access_token'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $claims = explode('.', $token)[1];

        return json_decode(base64_decode($claims), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function user()
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException;
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        $user = $this->mapUserToObject($this->getUserByToken(
            Arr::get($response, 'id_token')
        ));

        return $user
            ->setToken(Arr::get($response, 'access_token'))
            ->setRefreshToken(Arr::get($response, 'refresh_token'))
            ->setExpiresIn(Arr::get($response, 'expires_in'));
    }

    protected function mapUserToObject(array $user)
    {
        if (
            request()->filled("user")
            && array_key_exists("name", $user)
        ) {
            $user["name"] = Arr::get(json_decode(request("user"), true), 'name');
            $user['first_name'] = Arr::get($user, 'name.firstName');
            $user['last_name'] = Arr::get($user, 'name.lastName');
            $fullName = trim(
                ($user['first_name'] ?: "")
                    . " "
                    . ($user['last_name'] ?: "")
            );
        }

        return (new User)
            ->setRaw($user)
            ->map([
                "id" => $user["sub"],
                "name" => $fullName ?? null,
                "email" => $user["email"] ?? null,
            ]);
    }
}
