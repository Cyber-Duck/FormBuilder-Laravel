<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateFormSubmissionFieldsTable extends Migration
{
    protected $table = 'form_submission_fields';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->increments('id');
            $table->integer('entry_form_submission_id')->unsigned();
            $table->string('row_name',64)->nullable();
            $table->integer('group_index')->unsigned()->nullable(); // Used for cloneable RowGroups
            $table->string('field_name', 100);
            $table->text('value')->nullable();
            $table->date('date_value')->nullable();

            $table->integer('weight');
            $table->integer('randomiser')->nullable();

            $table->timestamps();

            $table->foreign('entry_form_submission_id')
                ->references('id')
                ->on('entry_form_submissions')
                ->onDelete('cascade');

            $table->index([
                'entry_form_submission_id',
                'row_name',
                'group_index','field_name'
            ], 'value_identifier_index');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        Schema::dropIfExists($this->table);
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }

}