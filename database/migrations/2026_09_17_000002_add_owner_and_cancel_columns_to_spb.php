<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOwnerAndCancelColumnsToSpb extends Migration
{
    public function up()
    {
        Schema::table('spb', function (Blueprint $table) {
            if (!Schema::hasColumn('spb', 'created_by_user_id')) {
                $table->unsignedInteger('created_by_user_id')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('spb', 'cancelled_by')) {
                $table->string('cancelled_by')->nullable()->after('approval_note');
            }
            if (!Schema::hasColumn('spb', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
            }
        });
    }

    public function down()
    {
        Schema::table('spb', function (Blueprint $table) {
            if (Schema::hasColumn('spb', 'created_by_user_id')) {
                $table->dropColumn('created_by_user_id');
            }
            if (Schema::hasColumn('spb', 'cancelled_by')) {
                $table->dropColumn('cancelled_by');
            }
            if (Schema::hasColumn('spb', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
        });
    }
}