<?php

namespace Skvn\Crud\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Skvn\Crud\Contracts\AclSubject;

class CrudUser extends CrudModel implements AuthenticatableContract, CanResetPasswordContract, AclSubject
{
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
    protected $guarded = ['id', 'remember_token', 'password_confirmation'];
    protected $fullname;

//    public function setPasswordAttribute($password)
//    {
//        if (! empty($password)) {
//            $this->attributes['password'] = bcrypt($password);
//        }
//    }

    public function getAcls()
    {
        $acl = \Config :: get('acl');

        return isset($acl['roles'][$this->acl_role]) ? $acl['roles'][$this->acl_role]['acls'] : [];
        //return $this->role->acls->lists('alias');
    }

    public function getAclsAttribute()
    {
        return $this->getAcls();
    }

    public function selectOptionsAcls()
    {
        $acls = [];
        foreach ($this->app['config']->get('acl.acls') as $acl => $caption) {
            $acls[] = ['value' => $acl, 'text' => $caption];
        }
    }

    public function selectOptionsAclRoles()
    {
        $roles = [];
        foreach ($this->app['config']->get('acl.roles') as $role_id => $role) {
            $roles[] = ['value' => $role_id, 'text' => $role['title']];
        }

        return $roles;
    }

    public function getFullNameAttribute()
    {
        if (! $this->fullname) {
            $this->fullname = $this->first_name.' '.$this->middle_name.' '.$this->last_name;
        }

        return $this->fullname;
    }

    public function getAclRoleTitleAttribute()
    {
        $acl = \Config :: get('acl');

        return isset($acl['roles'][$this->acl_role]) ? $acl['roles'][$this->acl_role]['title'] : null;
    }

    public function getAclRoleOptions()
    {
        $acl = \Config :: get('acl');
        $opts = [];
        foreach ($acl['roles'] as $id => $role) {
            $opts[$id] = $role['title'];
        }

        return $opts;
    }

    public function onBeforeCreate()
    {
        $count_all = self::count();
        if (! $count_all) {
            $this->acl_role = 'root';
        }

        return parent::onBeforeCreate();
    }
}
