<?php
namespace Wormhole\Protocols\LcV3\Models;

use Illuminate\Database\Eloquent\Model;
use Gbuckingham89\EloquentUuid\Traits\UuidForKey;
use Illuminate\Database\Eloquent\SoftDeletes;
class CardInfo extends Model
{
    use UuidForKey;
    //use SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'qianniu_card_info';
    /**
     * Indicates if the model should be timestamped.
     *  created_at and updated_at
     * @var bool
     */
    public $timestamps = TRUE;


    protected $primaryKey='id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

        'id','card_num','card_type','balance','user_password','start_card_date','effective_month'
    ];

    /**
     * 禁止批量赋值的
     * @var array
     */
    protected $guarded = [
           
    ];



}
