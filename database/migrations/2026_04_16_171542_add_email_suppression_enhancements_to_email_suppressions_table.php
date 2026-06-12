<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddEmailSuppressionEnhancementsToEmailSuppressionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('email_suppressions', function (Blueprint $table) {
            if (!Schema::hasColumn('email_suppressions', 'source')) {
                $table->string('source')->nullable()->after('reason');
                // Ej: precheck, send_exception, queued_job, webhook, parsed_bounce, manual
            }

            if (!Schema::hasColumn('email_suppressions', 'category')) {
                $table->string('category')->nullable()->after('source');
                // Ej: invalid_format, fake_domain, invalid_domain_dns, mailbox_full, user_not_found, etc.
            }

            if (!Schema::hasColumn('email_suppressions', 'soft_failures')) {
                $table->unsignedInteger('soft_failures')->default(0)->after('hits');
            }

            if (!Schema::hasColumn('email_suppressions', 'first_seen_at')) {
                $table->timestamp('first_seen_at')->nullable()->after('last_seen_at');
            }

            if (!Schema::hasColumn('email_suppressions', 'released_at')) {
                $table->timestamp('released_at')->nullable()->after('first_seen_at');
            }
        });

        // Backfill seguro para datos existentes
        DB::table('email_suppressions')
            ->whereNull('first_seen_at')
            ->update([
                'first_seen_at' => DB::raw('COALESCE(last_seen_at, created_at, CURRENT_TIMESTAMP)')
            ]);

        DB::table('email_suppressions')
            ->whereNull('source')
            ->update([
                'source' => DB::raw("
                    CASE
                        WHEN type = 'manual' THEN 'manual'
                        ELSE 'legacy'
                    END
                ")
            ]);

        DB::table('email_suppressions')
            ->whereNull('category')
            ->update([
                'category' => DB::raw("
                    CASE
                        WHEN type = 'manual' THEN 'manual_admin'
                        ELSE 'legacy_unknown'
                    END
                ")
            ]);

        // Los registros soft existentes cuentan al menos como 1 evento soft
        DB::table('email_suppressions')
            ->where('type', 'soft')
            ->where('soft_failures', 0)
            ->update([
                'soft_failures' => 1
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('email_suppressions', function (Blueprint $table) {
            if (Schema::hasColumn('email_suppressions', 'released_at')) {
                $table->dropColumn('released_at');
            }

            if (Schema::hasColumn('email_suppressions', 'first_seen_at')) {
                $table->dropColumn('first_seen_at');
            }

            if (Schema::hasColumn('email_suppressions', 'soft_failures')) {
                $table->dropColumn('soft_failures');
            }

            if (Schema::hasColumn('email_suppressions', 'category')) {
                $table->dropColumn('category');
            }

            if (Schema::hasColumn('email_suppressions', 'source')) {
                $table->dropColumn('source');
            }
        });
    }
}
