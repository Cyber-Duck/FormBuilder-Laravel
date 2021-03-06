<?php

namespace Nomensa\FormBuilder\Model;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\FormVersion;

class EntryForm extends Model
{


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function entryFormType()
    {
        return $this->belongsTo('App\EntryFormType');
    }


    public function formVersions()
    {
        return $this->hasMany('App\FormVersion');
    }


    public function getCurrentFormVersionAttribute() : FormVersion
    {
        return $this->formVersions()->isCurrent()->first();
    }


    public function setCurrentFormVersion(FormVersion $formVersion)
    {
        if ($formVersion->entry_form_id != $this->id) {
            throw new \Exception('FormVersion must belong to EntryForm');
        }

        // Set all other FormVersions to not current
        $this->formVersions()->where('id','<>',$formVersion->id)->update(['is_current'=>false]);

        return $formVersion->update(['is_current'=>true]);
    }


    public function formInstances()
    {
        return $this->hasManyThrough('App\FormInstance','App\FormVersion');
    }


    /**
     * @param array $users
     *
     * @return array
     */
    public function formInstanceIdsForUsers(array $users) : array
    {
        return $this->getFormInstanceIdsAttribute($users);
    }


    public function getFormInstanceIdsAttribute(array $users = null) : array
    {
        $q = DB::table('form_instances')
            ->leftJoin('form_versions','form_version_id','=','form_versions.id')
            ->select('form_instances.id as form_instances_id')
            ->where('entry_form_id',$this->id);

        if ($users) {
            $q->whereIn('form_instances.user_id', $users);
        }

        return $q->get()
            ->pluck('form_instances_id')
            ->toArray();
    }


    public function parentEntryForm()
    {
        return $this->belongsTo('App\EntryForm','id','form_child_id');
    }


    public function childEntryForm()
    {
        return $this->hasOne('App\EntryForm','id','form_child_id');
    }


    public function scopeLive($query)
    {
        return $query->where('live',1);
    }


    public function scopeNotLive($query)
    {
        return $query->where('live',0);
    }

}
