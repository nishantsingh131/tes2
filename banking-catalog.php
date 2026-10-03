<?php

declare(strict_types=1);

function banking_catalog(): array
{
    return [
        'sbi-po' => ['title' => 'SBI PO', 'group' => 'SBI', 'stage' => 'Prelims & Mains', 'description' => 'Officer-level practice for reasoning, data analysis, English and banking awareness.'],
        'sbi-clerk' => ['title' => 'SBI Clerk', 'group' => 'SBI', 'stage' => 'Prelims & Mains', 'description' => 'Junior associate practice covering speed, accuracy and customer-facing banking aptitude.'],
        'sbi-cbo' => ['title' => 'SBI CBO', 'group' => 'SBI', 'stage' => 'Online Exam', 'description' => 'Circle-based officer practice with banking, reasoning, English and professional knowledge.'],
        'sbi-so' => ['title' => 'SBI SO', 'group' => 'SBI', 'stage' => 'Online Exam', 'description' => 'Specialist officer practice with aptitude, reasoning, English and role-ready banking knowledge.'],
        'sbi-apprentice' => ['title' => 'SBI Apprentice', 'group' => 'SBI', 'stage' => 'Online Exam', 'description' => 'Apprentice-level practice for general awareness, quantitative aptitude and local language readiness.'],
        'ibps-po' => ['title' => 'IBPS PO', 'group' => 'IBPS', 'stage' => 'Prelims & Mains', 'description' => 'Probationary officer practice with reasoning, quant, English and banking awareness.'],
        'ibps-clerk' => ['title' => 'IBPS Clerk', 'group' => 'IBPS', 'stage' => 'Prelims & Mains', 'description' => 'Clerical cadre practice focused on speed, accuracy and everyday banking aptitude.'],
        'ibps-rrb-po' => ['title' => 'IBPS RRB PO', 'group' => 'IBPS', 'stage' => 'Prelims & Mains', 'description' => 'Regional rural bank officer practice with rural economy and banking awareness.'],
        'ibps-rrb-clerk' => ['title' => 'IBPS RRB Clerk', 'group' => 'IBPS', 'stage' => 'Prelims & Mains', 'description' => 'Regional rural bank office assistant practice for reasoning, numeracy and awareness.'],
        'ibps-so' => ['title' => 'IBPS SO', 'group' => 'IBPS', 'stage' => 'Prelims & Professional Knowledge', 'description' => 'Specialist officer practice with common aptitude and professional banking knowledge.'],
        'rbi-grade-b' => ['title' => 'RBI Grade B', 'group' => 'RBI', 'stage' => 'Phase I & II', 'description' => 'Reserve Bank officer practice for economics, finance, reasoning, English and current banking concepts.'],
        'rbi-assistant' => ['title' => 'RBI Assistant', 'group' => 'RBI', 'stage' => 'Prelims & Mains', 'description' => 'Assistant-level practice for reasoning, numerical ability, English and banking awareness.'],
        'rbi-grade-a' => ['title' => 'RBI Grade A', 'group' => 'RBI', 'stage' => 'Phase I & II', 'description' => 'Specialist regulatory officer practice with aptitude and finance-focused preparation.'],
        'nabard-grade-a' => ['title' => 'NABARD Grade A', 'group' => 'NABARD', 'stage' => 'Phase I & II', 'description' => 'Development banking officer practice with agriculture, rural development and finance.'],
        'nabard-grade-b' => ['title' => 'NABARD Grade B', 'group' => 'NABARD', 'stage' => 'Phase I & II', 'description' => 'Manager-level development banking practice with rural economy and financial awareness.'],
        'nabard-development-assistant' => ['title' => 'NABARD Development Assistant', 'group' => 'NABARD', 'stage' => 'Prelims & Mains', 'description' => 'Development assistant practice for reasoning, English, numeracy and rural awareness.'],
        'sebi-grade-a' => ['title' => 'SEBI Grade A', 'group' => 'SEBI', 'stage' => 'Phase I & II', 'description' => 'Securities market regulator officer practice with finance and aptitude preparation.'],
        'sebi-grade-b' => ['title' => 'SEBI Grade B', 'group' => 'SEBI', 'stage' => 'Phase I & II', 'description' => 'Manager-level securities market practice with finance, economics and reasoning.'],
        'lic-aao' => ['title' => 'LIC AAO', 'group' => 'LIC', 'stage' => 'Prelims & Mains', 'description' => 'Assistant administrative officer practice for insurance, aptitude, reasoning and English.'],
        'lic-assistant' => ['title' => 'LIC Assistant', 'group' => 'LIC', 'stage' => 'Prelims & Mains', 'description' => 'Insurance assistant practice for numerical ability, reasoning, English and awareness.'],
        'lic-ado' => ['title' => 'LIC ADO', 'group' => 'LIC', 'stage' => 'Prelims & Mains', 'description' => 'Apprentice development officer practice with insurance and sales aptitude.'],
        'sidbi-grade-a' => ['title' => 'SIDBI Grade A', 'group' => 'Other Banking', 'stage' => 'Phase I & II', 'description' => 'Small industries development bank officer practice with finance and MSME awareness.'],
        'sidbi-grade-b' => ['title' => 'SIDBI Grade B', 'group' => 'Other Banking', 'stage' => 'Phase I & II', 'description' => 'Manager-level MSME finance practice with banking, economics and aptitude.'],
        'ecgc-po' => ['title' => 'ECGC PO', 'group' => 'Other Banking', 'stage' => 'Online Exam', 'description' => 'Export credit officer practice with banking, trade finance and reasoning.'],
        'pfrda-grade-a' => ['title' => 'PFRDA Grade A', 'group' => 'Other Banking', 'stage' => 'Phase I & II', 'description' => 'Pension regulator officer practice with finance, markets and aptitude.'],
        'niacl-ao' => ['title' => 'NIACL AO', 'group' => 'Other Banking', 'stage' => 'Prelims & Mains', 'description' => 'Administrative officer practice for insurance, reasoning, quant and English.'],
        'uiic-ao' => ['title' => 'UIIC AO', 'group' => 'Other Banking', 'stage' => 'Prelims & Mains', 'description' => 'Insurance administrative officer practice with aptitude and insurance awareness.'],
    ];
}

function banking_labels(): array
{
    $catalog = banking_catalog();
    $labels = array_combine(array_keys($catalog), array_column($catalog, 'title')) ?: [];
    if (function_exists('series_records')) {
        foreach (series_records() as $record) {
            if (isset($record['slug'], $record['title'])) $labels[(string) $record['slug']] = (string) $record['title'];
        }
    }
    return $labels;
}

function banking_questions(string $slug): array
{
    $title = banking_catalog()[$slug]['title'] ?? 'Banking';
    return [
        ['q' => "{$title}: The rate at which the RBI lends short-term funds to commercial banks against securities is called?", 'options' => ['Repo rate', 'Reverse repo rate', 'Bank rate', 'CRR'], 'answer' => 0, 'topic' => 'Banking awareness'],
        ['q' => "{$title}: What is 15% of 240?", 'options' => ['24', '36', '42', '48'], 'answer' => 1, 'topic' => 'Quantitative aptitude'],
        ['q' => "{$title}: Find the next number: 3, 6, 12, 24, ?", 'options' => ['36', '48', '54', '60'], 'answer' => 1, 'topic' => 'Reasoning'],
        ['q' => "{$title}: Choose the word closest in meaning to 'prudent'.", 'options' => ['Careless', 'Wise', 'Rapid', 'Doubtful'], 'answer' => 1, 'topic' => 'English language'],
        ['q' => "{$title}: Which institution is responsible for regulating monetary policy in India?", 'options' => ['SEBI', 'RBI', 'IRDAI', 'PFRDA'], 'answer' => 1, 'topic' => 'Financial awareness'],
        ['q' => "{$title}: If the simple interest on Rs. 2,000 for 2 years is Rs. 240, what is the annual rate?", 'options' => ['4%', '5%', '6%', '8%'], 'answer' => 2, 'topic' => 'Quantitative aptitude'],
        ['q' => "{$title}: In a coding pattern, BANK is written as CBOL. How is LOAN written using the same pattern?", 'options' => ['MPBO', 'KNZM', 'MPCB', 'LPZM'], 'answer' => 0, 'topic' => 'Reasoning'],
        ['q' => "{$title}: Which account is generally used for frequent business transactions?", 'options' => ['Current account', 'Fixed deposit', 'Recurring deposit', 'Pension account'], 'answer' => 0, 'topic' => 'Banking awareness'],
        ['q' => "{$title}: Choose the correctly spelt word.", 'options' => ['Accomodation', 'Accommodation', 'Acommodation', 'Accommadation'], 'answer' => 1, 'topic' => 'English language'],
        ['q' => "{$title}: Financial inclusion primarily aims to provide affordable financial services to?", 'options' => ['Only large companies', 'All sections, especially underserved groups', 'Only foreign investors', 'Only government departments'], 'answer' => 1, 'topic' => 'Financial awareness'],
    ];
}

function banking_test_sets(string $slug, int $count = 15, int $duration = 10): array
{
    $title = banking_catalog()[$slug]['title'] ?? 'Banking';
    $sets = [];
    for ($number = 1; $number <= $count; $number++) {
        $key = 'test-' . str_pad((string) $number, 2, '0', STR_PAD_LEFT);
        $sets[$key] = ['title' => $title . ' Full-Length Set ' . str_pad((string) $number, 2, '0', STR_PAD_LEFT), 'duration_minutes' => $duration, 'questions' => banking_questions($slug)];
    }
    return $sets;
}
