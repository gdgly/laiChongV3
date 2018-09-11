<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQianNiuCardInfoTable extends Migration
{


    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('qianniu_card_info', function (Blueprint $table) {

            //桩信息
            $table->uuid('id',32)->comment('卡信息ID');
            $table->string('card_num',10)->unique()->comment('卡号');
            $table->tinyInteger('card_type',FALSE,TRUE)->default(0)->comment('卡类型');
            $table->integer('balance')->unsigned()->default(0)->comment('剩余金额/时间 单位分/分钟');
            $table->string('user_password',10)->default('')->comment('用户密码');
            $table->timestamp('start_card_date')->nullable()->comment('开卡时间(年月日)');
            $table->tinyInteger('effective_month',FALSE,TRUE)->default(0)->comment('有效月数');

            $table->timestamps(); 
            $table->softDeletes();
            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('qianniu_card_info');
    }
}
