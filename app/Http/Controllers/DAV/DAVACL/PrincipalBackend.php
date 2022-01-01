<?php

namespace App\Http\Controllers\DAV\DAVACL;

use Sabre\DAV;
use App\Traits\WithUser;
use Illuminate\Support\Str;
use Sabre\DAV\Server as SabreServer;
use Sabre\DAVACL\PrincipalBackend\AbstractBackend;

class PrincipalBackend extends AbstractBackend
{
    use WithUser;

    /**
     * This is the prefix that will be used to generate principal urls.
     *
     * @var string
     */
    public const PRINCIPAL_PREFIX = 'principals/';

    /**
     * Get the principal for user.
     *
     * @return string
     */
    public static function getPrincipalUser($user): string
    {
        return static::PRINCIPAL_PREFIX.$user->email;
    }

    protected function getPrincipals()
    {
        return [
            [
                'uri'               => static::getPrincipalUser($this->user),
                '{DAV:}displayname' => $this->user->name,
                '{'.SabreServer::NS_SABREDAV.'}email-address' => $this->user->email,
            ],
        ];
    }

    /**
     * Returns a list of principals based on a prefix.
     *
     * This prefix will often contain something like 'principals'. You are only
     * expected to return principals that are in this base path.
     *
     * You are expected to return at least a 'uri' for every user, you can
     * return any additional properties if you wish so. Common properties are:
     *   {DAV:}displayname
     *   {http://sabredav.org/ns}email-address - This is a custom SabreDAV
     *     field that's actually injected in a number of other properties. If
     *     you have an email address, use this property.
     *
     * @param  string  $prefixPath
     * @return array
     */
    public function getPrincipalsByPrefix($prefixPath)
    {
        $prefixPath = Str::finish($prefixPath, '/');

        return array_filter($this->getPrincipals(), function ($principal) use ($prefixPath) {
            return ! $prefixPath || strpos($principal['uri'], $prefixPath) == 0;
        });
    }

    /**
     * Returns a specific principal, specified by its path.
     * The returned structure should be the exact same as from
     * getPrincipalsByPrefix.
     *
     * @param  string  $path
     * @return array
     */
    public function getPrincipalByPath($path)
    {
        foreach ($this->getPrincipalsByPrefix(static::PRINCIPAL_PREFIX) as $principal) {
            if ($principal['uri'] === $path) {
                return $principal;
            }
        }

        return [];
    }

    /**
     * Updates one ore more webdav properties on a principal.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param  string  $path
     * @param  \Sabre\DAV\PropPatch  $propPatch
     * @return void
     */
    public function updatePrincipal($path, DAV\PropPatch $propPatch)
    {
    }

    /**
     * This method is used to search for principals matching a set of
     * properties.
     *
     * This search is specifically used by RFC3744's principal-property-search
     * REPORT.
     *
     * The actual search should be a unicode-non-case-sensitive search. The
     * keys in searchProperties are the WebDAV property names, while the values
     * are the property values to search on.
     *
     * By default, if multiple properties are submitted to this method, the
     * various properties should be combined with 'AND'. If $test is set to
     * 'anyof', it should be combined using 'OR'.
     *
     * This method should simply return an array with full principal uri's.
     *
     * If somebody attempted to search on a property the backend does not
     * support, you should simply return 0 results.
     *
     * You can also just return 0 results if you choose to not support
     * searching at all, but keep in mind that this may stop certain features
     * from working.
     *
     * @param  string  $prefixPath
     * @param  array  $searchProperties
     * @param  string  $test
     * @return array
     */
    public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof')
    {
        $result = [];
        $principals = $this->getPrincipalsByPrefix($prefixPath);
        if (! $principals) {
            return $result;
        }

        foreach ($principals as $principal) {
            $ok = false;
            foreach ($searchProperties as $key => $value) {
                if ($principal[$key] == $value) {
                    $ok = true;
                } elseif ($test == 'allof') {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                $result[] = $principal['uri'];
            }
        }

        return $result;
    }

    /**
     * Returns the list of members for a group-principal.
     *
     * @param  string  $principal
     * @return array
     */
    public function getGroupMemberSet($principal)
    {
        $principal = $this->getPrincipalByPath($principal);
        if (! $principal) {
            return [];
        }

        return [
            $principal['uri'],
        ];
    }

    /**
     * Returns the list of groups a principal is a member of.
     *
     * @param  string  $principal
     * @return array
     */
    public function getGroupMembership($principal)
    {
        return $this->getGroupMemberSet($principal);
    }

    /**
     * Updates the list of group members for a group principal.
     *
     * The principals should be passed as a list of uri's.
     *
     * @param  string  $principal
     * @param  array  $members
     * @return void
     */
    public function setGroupMemberSet($principal, array $members)
    {
    }
}
