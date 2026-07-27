<?php

use App\Support\CrmAcademicMapper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->string('tenth_score', 40)->nullable()->after('country_interest');
            $table->unsignedSmallInteger('tenth_passing_year')->nullable()->after('tenth_score');
            $table->string('twelfth_score', 40)->nullable()->after('tenth_passing_year');
            $table->unsignedSmallInteger('twelfth_passing_year')->nullable()->after('twelfth_score');
            $table->string('graduation_score', 40)->nullable()->after('twelfth_passing_year');
            $table->unsignedSmallInteger('graduation_passing_year')->nullable()->after('graduation_score');
            $table->string('backlogs', 40)->nullable()->after('graduation_passing_year');
            // Repeatable rows of ['test', 'name', 'score', 'date'] — a student can
            // sit several tests, and "other" carries a free-text test name.
            $table->json('english_tests')->nullable()->after('backlogs');
            $table->json('aptitude_tests')->nullable()->after('english_tests');
        });

        // Backfill from the questionnaire answers already stored against each lead.
        DB::table('crm_website_submissions')
            ->orderByDesc('submitted_at')
            ->select(['crm_lead_id', 'sections'])
            ->get()
            ->groupBy('crm_lead_id')
            ->each(function ($rows, $leadId): void {
                $mapped = CrmAcademicMapper::fromSubmissions(
                    $rows->map(fn ($row): array => json_decode((string) $row->sections, true) ?: [])
                );
                if ($mapped === []) {
                    return;
                }
                foreach (CrmAcademicMapper::TEST_FIELDS as $field) {
                    if (isset($mapped[$field])) {
                        $mapped[$field] = json_encode($mapped[$field]);
                    }
                }
                DB::table('crm_leads')->where('id', $leadId)->update($mapped);
            });
    }

    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->dropColumn(CrmAcademicMapper::FIELDS);
        });
    }
};
