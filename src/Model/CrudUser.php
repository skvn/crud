<?php namespace Skvn\Crud\Model;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Skvn\Crud\Model\CrudModel;
use Skvn\Crud\Contracts\AclSubject;
use Skvn\Crud\Traits\PrefTrait;

class CrudUser extends CrudModel implements AuthenticatableContract, CanResetPasswordContract, AclSubject {


	use Authenticatable, CanResetPassword;


	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'users';

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = ['password', 'remember_token'];
    protected $guarded = array('id','remember_token','password_confirmation');
    protected $fullname;


    public function setPasswordAttribute( $password ) {
        $this->attributes['password'] = \Hash::make( $password );
    }


    public function getAcls()
    {
        $acl = \Config :: get("acl");
        return isset($acl['roles'][$this->acl_role]) ? $acl['roles'][$this->acl_role]['acls'] : array();
        //return $this->role->acls->lists('alias');

    }

    public function getAclsAttribute()
    {
        return $this->getAcls();

    }


    public function getFullNameAttribute()
    {

        if (!$this->fullname)
        {
            $this->fullname = $this->first_name.' '.$this->middle_name.' '.$this->last_name;
        }
        return $this->fullname;
    }



    public function getAclRoleTitleAttribute()
    {
        $acl = \Config :: get("acl");
        return isset($acl['roles'][$this->acl_role]) ? $acl['roles'][$this->acl_role]['title'] : null;
    }

    function getAclRoleOptions()
    {
        $acl = \Config :: get("acl");
        $opts = [];
        foreach ($acl['roles'] as $id => $role)
        {
            $opts[$id] = $role['title'];
        }
        return $opts;
    }






}
