<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableAnswers extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('answers', function (Blueprint $table) {
      $table->increments('id');
      $table->text('content');
      $table->unsignedInteger('user_id');
      $table->unsignedInteger('question_id');
      $table->timestamps();

      $table->foreign('user_id')->references('id')->on('users');
      $table->foreign('question_id')->references('id')->on('questions');
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    Schema::drop('answers');
  }
}
