<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->string('lead_origin', 30)->default('manual')->after('source')->index();
            $table->string('lead_type', 50)->default('general')->after('lead_origin')->index();
        });

        Schema::create('crm_subscribers', function (Blueprint $table): void {
            $table->id();
            $table->string('email', 190)->unique();
            $table->string('source', 120)->nullable()->index();
            $table->string('status', 30)->default('active')->index();
            $table->timestamp('subscribed_at')->index();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });

        DB::table('crm_leads')->whereNotNull('created_by')->update([
            'lead_origin' => 'manual',
            'lead_type' => 'general',
        ]);

        $typeMap = [
            'profiler' => 'student_profiler',
            'loan-acco' => 'loan_accommodation',
            'sop' => 'statement_of_purpose',
            'visa-mock' => 'visa_mock_interview',
            'career-library' => 'career_library',
        ];

        foreach ($typeMap as $source => $type) {
            $leadIds = DB::table('crm_website_submissions')->where('source', $source)->pluck('crm_lead_id');
            if ($leadIds->isNotEmpty()) {
                DB::table('crm_leads')->whereIn('id', $leadIds)->update([
                    'lead_type' => $type,
                ]);
                DB::table('crm_leads')->whereIn('id', $leadIds)->whereNull('created_by')->update(['lead_origin' => 'website']);
            }
        }

        $paymentLeadIds = DB::table('payment_attempts')->whereNotNull('crm_lead_id')->pluck('crm_lead_id');
        if ($paymentLeadIds->isNotEmpty()) {
            DB::table('crm_leads')->whereIn('id', $paymentLeadIds)
                ->where('lead_origin', 'manual')
                ->whereNull('created_by')
                ->update(['lead_origin' => 'enrollment', 'lead_type' => 'enrollment']);
        }

        $newsletterRows = DB::table('crm_website_submissions')
            ->leftJoin('crm_leads', 'crm_leads.id', '=', 'crm_website_submissions.crm_lead_id')
            ->where('crm_website_submissions.source', 'newsletter')
            ->get([
                'crm_website_submissions.id',
                'crm_website_submissions.crm_lead_id',
                'crm_website_submissions.source_label',
                'crm_website_submissions.meta',
                'crm_website_submissions.submitted_at',
                'crm_leads.email as lead_email',
                'crm_leads.created_by',
            ]);

        foreach ($newsletterRows as $row) {
            $meta = json_decode((string) $row->meta, true);
            $email = strtolower(trim((string) ($meta['email'] ?? $row->lead_email ?? '')));
            if ($email !== '') {
                DB::table('crm_subscribers')->updateOrInsert(
                    ['email' => $email],
                    [
                        'source' => $row->source_label ?: 'Newsletter',
                        'status' => 'active',
                        'subscribed_at' => $row->submitted_at ?: now(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }
        }

        DB::table('crm_website_submissions')->where('source', 'newsletter')->delete();
        DB::table('crm_website_submissions')->whereIn('source', ['enrollment', 'test-prep-enrollment'])->delete();

        foreach ($newsletterRows->pluck('crm_lead_id')->unique() as $leadId) {
            $lead = DB::table('crm_leads')->where('id', $leadId)->first();
            if (! $lead || $lead->created_by !== null) {
                continue;
            }

            $hasSubmission = DB::table('crm_website_submissions')->where('crm_lead_id', $leadId)->exists();
            $hasPayment = DB::table('payment_attempts')->where('crm_lead_id', $leadId)->exists();
            if (! $hasSubmission && ! $hasPayment && str_contains(strtolower((string) $lead->source), 'newsletter')) {
                DB::table('crm_leads')->where('id', $leadId)->update(['deleted_at' => now()]);
            }
        }

        if (! app()->runningUnitTests()) {
            app(\App\Services\LegacyWebsiteLeadImporter::class)->import();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_subscribers');
        Schema::table('crm_leads', function (Blueprint $table): void {
            $table->dropColumn(['lead_origin', 'lead_type']);
        });
    }
};
