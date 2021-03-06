<?php

namespace PhalconRestJWT\Security;

use \Phalcon\Acl\Adapter\Memory as AclList;

/**
 * Class AclRoles
 * @package PhalconRestJWT
 */
class AclRoles {

    /**
     * Setup an ACL rules
     * @param array $resources
     * @param array $roles
     * @return FALSE|void
     */
    public static function setAcl($resources,$roles) {
        //Create the ACL
        $acl = new \Phalcon\Acl\Adapter\Memory();
        //The default action is DENY access
        $acl->setDefaultAction(\Phalcon\Acl::DENY);

        /*
         * ROLES
         * Owner - can do anything
         * Admin - can do most things
         * Staff - rRestricted User
         * */

        foreach ($roles as $role) {
            $acl->addRole(new \Phalcon\Acl\Role($role));
        }

        /*
         * RESOURCES
         * for each user, specify the 'controller' and 'method' they have access to (user=>[controller=>[method,method]],...)
         * this is created in an array as we later loop over this structure to assign users to resources,
         * on each route you can assign a code like in the example R1, R2 in the routes example.
         * */

        foreach($resources as $arrResource){
            foreach($arrResource as $controller=>$arrMethods){
                $acl->addResource(new \Phalcon\Acl\Resource($controller),$arrMethods);
            }
        }

        /*
         * ACCESS
         * */
        foreach ($acl->getRoles() as $objRole) {
            $roleName = $objRole->getName();
                foreach ($resources[$roleName] as $resource => $method) {
                    $acl->allow($roleName,$resource,$method);
                }
        }


        try{
            file_put_contents(APP_DIR."/cache/acl.data", serialize($acl));
            return true;
        }
        catch (\Exception $e){
            throw new \Micro\Exceptions\HTTPExceptions(
                $e->getMessage(),
                500,
                array(
                    'dev' => 'Internal Error.',
                    'internalCode' => 'ACL0001',
                    'more' => ''
                )
            );
        }


    }

    /**
     * Get User ACL rules
     * @param string $user
     * @param string $activeHandler
     * @param string $handler
     * @return bollean
     */
    public static function getAcl($user,$activeHandler, $handler){

        // Restore acl object from serialized file
        $acl = unserialize(file_get_contents(APP_DIR."/cache/acl.data"));
        
        $allowed = $acl->isAllowed($user, $activeHandler, $handler);

        return $allowed;
    }
}