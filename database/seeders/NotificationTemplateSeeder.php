<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'invoice_control_number',
                'name' => 'Invoice & Control Number Issued',
                'channel' => 'email',
                'subject' => 'Your OUT LMS Invoice & Control Number ({control_number})',
                'body' => "Hello {student_name},\n\nYour registration for {course_title} at {branch_name} is confirmed as pending payment.\n\nInvoice Number: {invoice_number}\nGePG Control Number: {control_number}\nAmount Due: {amount_due}\nDue Date: {due_date}\n\nYou may pay using your control number or upload your receipt via your Student Portal.",
                'variables' => ['student_name', 'course_title', 'branch_name', 'invoice_number', 'control_number', 'amount_due', 'due_date'],
                'is_active' => true,
            ],
            [
                'key' => 'installment_reminder',
                'name' => 'Installment Due Reminder',
                'channel' => 'email',
                'subject' => 'Installment #{installment_number} Payment Reminder - {course_title}',
                'body' => "Hello {student_name},\n\nThis is a friendly reminder that installment #{installment_number} ({amount_due}) for {course_title} is due on {due_date}.\n\nControl Number: {control_number}\nInvoice Number: {invoice_number}\nRemaining Balance: {balance}\n\nPlease settle on time to remain eligible for your certificate.",
                'variables' => ['student_name', 'course_title', 'installment_number', 'amount_due', 'due_date', 'control_number', 'invoice_number', 'balance'],
                'is_active' => true,
            ],
            [
                'key' => 'overdue_notice',
                'name' => 'Overdue Installment Notice',
                'channel' => 'email',
                'subject' => 'Action Required: Overdue Installment for {course_title}',
                'body' => "Hello {student_name},\n\nYour installment #{installment_number} of {amount_due} for {course_title} (Invoice {invoice_number}) is overdue since {due_date}.\n\nControl Number: {control_number}\nOutstanding Balance: {balance}\n\nPlease clear the balance or upload payment proof in your Student Portal.",
                'variables' => ['student_name', 'course_title', 'installment_number', 'amount_due', 'due_date', 'control_number', 'invoice_number', 'balance'],
                'is_active' => true,
            ],
            [
                'key' => 'payment_verified',
                'name' => 'Payment Verified Confirmation',
                'channel' => 'email',
                'subject' => 'Payment Received & Verified - {invoice_number}',
                'body' => "Hello {student_name},\n\nWe have verified your payment of {amount_paid} for {course_title}.\n\nInvoice Number: {invoice_number}\nReceipt Number: {receipt_number}\nRemaining Balance: {balance}\n\nThank you for learning with OUT LMS.",
                'variables' => ['student_name', 'course_title', 'amount_paid', 'invoice_number', 'receipt_number', 'balance'],
                'is_active' => true,
            ],
            [
                'key' => 'certificate_ready',
                'name' => 'Certificate Ready for Issue',
                'channel' => 'email',
                'subject' => 'Your Certificate for {course_title} is Ready ({certificate_serial})',
                'body' => "Congratulations {student_name}!\n\nYou have met the attendance and tuition requirements for {course_title}. Your certificate ({certificate_serial}) is ready/issued and can be verified or downloaded from your Student Portal.",
                'variables' => ['student_name', 'course_title', 'certificate_serial'],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $tpl) {
            NotificationTemplate::updateOrCreate(['key' => $tpl['key']], $tpl);
        }
    }
}
