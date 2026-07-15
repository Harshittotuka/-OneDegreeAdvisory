<?php

namespace App\Support;

class CrmOptions
{
    public const STATUSES = [
        'new' => 'New lead', 'not_answered' => 'Not answered', 'call_back' => 'Call back',
        'follow_up' => 'Follow up', 'interested' => 'Interested', 'not_interested' => 'Not interested',
        'converted' => 'Converted / enrolled', 'junk' => 'Junk / invalid', 'dropped' => 'Dropped',
    ];

    public const PRIORITIES = ['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'];

    public const CATEGORIES = [
        'undergraduate' => 'Undergraduate', 'postgraduate' => 'Postgraduate', 'mbbs' => 'MBBS',
        'test_prep' => 'Test preparation', 'visa' => 'Visa', 'other' => 'Other',
    ];

    public const STUDENT_STAGES = [
        'doc_pending' => 'Documentation pending', 'doc_complete' => 'Documents complete',
        'app_submitted' => 'Application submitted', 'offer_received' => 'Offer letter received',
        'deposit_paid' => 'Deposit / tuition paid', 'visa_in_process' => 'Visa in process',
        'visa_filed' => 'Visa filed', 'visa_granted' => 'Visa granted',
        'visa_rejected' => 'Visa rejected', 'dropped' => 'Student dropped',
    ];

    public const STUDENT_CATEGORIES = [
        'paid' => 'Paid student', 'non_paid' => 'Non-paid student', 'enrollment_fee_paid' => 'Enrollment fee paid',
    ];
}
