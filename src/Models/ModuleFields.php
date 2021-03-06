<?php

namespace Dwij\Laraadmin\Models;

use Illuminate\Database\Eloquent\Model;
use Schema;
use Log;

use Dwij\Laraadmin\Models\Module;

class ModuleFields extends Model
{
    protected $table = 'module_fields';
    
    protected $fillable = [
        "colname", "label", "module", "field_type", "readonly", "defaultvalue", "minlength", "maxlength", "required", "popup_vals"
    ];
    
    protected $hidden = [
        
    ];
    
    public static function createField($request) {
        $module = Module::find($request->module_id);
        $module_id = $request->module_id;
        
        $field = ModuleFields::where('colname', $request->colname)->where('module', $module_id)->first();
        if(!isset($field->id)) {
            $field = new ModuleFields;
            $field->colname = $request->colname;
            $field->label = $request->label;
            $field->module = $request->module_id;
            $field->field_type = $request->field_type;
            if($request->readonly) {
                $field->readonly = true;
            } else {
                $field->readonly = false;
            }
            $field->defaultvalue = $request->defaultvalue;
            $field->minlength = $request->minlength;
            $field->maxlength = $request->maxlength;
            if($request->required) {
                $field->required = true;
            } else {
                $field->required = false;
            }
            $field->popup_vals = $request->popup_vals;
            $field->save();
            
            // Create Schema for Module Field
            if (!Schema::hasTable($module->name_db)) {
                Schema::create($module->name_db, function($table) {
                    $table->increments('id');
                    $table->timestamps();
                });
            }
            Schema::table($module->name_db, function($table) use ($field) {
                $table->string($field->colname);
                // createUpdateFieldSchema()
            });
        }
        return $field->id;
    }

    public static function updateField($id, $request) {
        $module_id = $request->module_id;
        
        $field = ModuleFields::find($id);

        // Update the Schema
        // Change Column Name if Different
        $module = Module::find($module_id);
        if($field->colname != $request->colname) {
            Schema::table($module->name_db, function ($table) use ($field, $request) {
                $table->renameColumn($field->colname, $request->colname);
            });
        }
        
        // Update Context in ModuleFields
        $field->colname = $request->colname;
        $field->label = $request->label;
        $field->module = $request->module_id;
        $field->field_type = $request->field_type;
        if($request->readonly) {
            $field->readonly = true;
        } else {
            $field->readonly = false;
        }
        $field->defaultvalue = $request->defaultvalue;
        $field->minlength = $request->minlength;
        $field->maxlength = $request->maxlength;
        if($request->required) {
            $field->required = true;
        } else {
            $field->required = false;
        }
        if($request->field_type == 7 || $request->field_type == 15 || $request->field_type == 18 || $request->field_type == 20) {
            if($request->popup_value_type == "table") {
                $field->popup_vals = "@".$request->popup_vals_table;
            } else if($request->popup_value_type == "list") {
                $request->popup_vals_list = json_encode($request->popup_vals_list);
                $field->popup_vals = $request->popup_vals_list;
            }
        }
        $field->save();

        Schema::table($module->name_db, function ($table) use ($field) {
            Module::create_field_schema($table, $field, true);
        });
    }
}
