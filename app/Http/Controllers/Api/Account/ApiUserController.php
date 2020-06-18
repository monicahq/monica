<?php

namespace App\Http\Controllers\Api\Account;

use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use App\Models\Settings\Term;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Services\User\AcceptPolicy;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Account\User\User as UserResource;
use App\Http\Resources\Settings\Compliance\Compliance as ComplianceResource;
use function auth;

class ApiUserController extends ApiController {

    /**
     * Get the detail of the authenticated user.
     *
     * @param Request $request
     * @return UserResource
     */
    public function show( Request $request ) : UserResource
    {
        return new UserResource( auth() -> user() );
    }

    /**
     * Get the state of a specific term for the user.
     *
     * @param Request $request
     * @param int     $termId
     * @return JsonResponse
     */
    public function get( Request $request, $termId )
    {
        try {
            $term = Term ::findOrFail( $termId );
        } catch( ModelNotFoundException $e ) {
            return $this -> respondNotFound();
        }
        $termUser = $this -> getUserTerm( $term );
        if( $termUser ) {
            $data = [
                'signed'      => TRUE,
                'signed_date' => DateHelper ::getTimestamp( $termUser -> created_at ),
                'ip_address'  => $termUser -> ip_address,
                'user'        => new UserResource( auth() -> user() ),
                'term'        => new ComplianceResource( $term ),
            ];
        } else {
            return $this -> respondNotFound();
        }

        return $this -> respond( [
            'data' => $data,
        ] );
    }

    /**
     * @param $term
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|object|null
     */
    protected function getUserTerm( $term )
    {
        return DB ::table( 'term_user' ) -> where( 'user_id', auth() -> id() )
                  -> where( 'account_id', auth() -> user() -> account_id )
                  -> where( 'term_id', $term -> id )
                  -> first();
    }

    /**
     * Get all the policies ever signed by the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSignedPolicies( Request $request )
    {
        $terms        = collect();
        $termsForUser = auth() -> user() -> terms;
        if( $termsForUser -> count() == 0 ) {
            return $this -> respondNotFound();
        }
        $termsForUser -> flatten() -> map( function ( $term ) use ( $terms ) {
            $terms -> push( [
                'signed'      => TRUE,
                'signed_date' => DateHelper ::getTimestamp( $term -> created_at ),
                'ip_address'  => $term -> pivot -> ip_address,
                'user'        => new UserResource( auth() -> user() ),
                'term'        => new ComplianceResource( $term ),
            ] );
        } );

        return $this -> respond( [
            'data' => $terms,
        ] );
    }

    /**
     * Sign the latest policy for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function set( Request $request )
    {
        $validator = Validator ::make( $request -> all(), [
            'ip_address' => 'required',
        ] );
        if( $validator -> fails() ) {
            return $this -> respondValidatorFailed( $validator );
        }
        try {
            $term = app( AcceptPolicy::class ) -> execute( [
                'account_id' => auth() -> user() -> account_id,
                'user_id'    => auth() -> id(),
                'ip_address' => $request -> input( 'ip_address' ),
            ] );
        } catch( QueryException $e ) {
            return $this -> respondInvalidQuery();
        }
        try {
            $termUser = $this -> getUserTerm( $term );
        } catch( ModelNotFoundException $e ) {
            return $this -> respondInvalidQuery();
        }

        return $this -> respond( [
            'data' => [
                'signed'      => TRUE,
                'signed_date' => DateHelper ::getTimestamp( $termUser -> created_at ),
                'ip_address'  => $termUser -> ip_address,
                'user'        => new UserResource( auth() -> user() ),
                'term'        => new ComplianceResource( $term ),
            ],
        ] );
    }
}
