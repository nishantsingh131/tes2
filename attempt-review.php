<?php

declare(strict_types=1);

function attempt_review_questions(array $attempt, string $userId, bool $sample = false): ?array
{
    $attemptId = (string) ($attempt['id'] ?? '');
    if ($attemptId === '') return null;

    if (db_enabled() && $userId !== '') {
        $pdo = db_pdo();
        if ($pdo === null) return null;
        $stmt = $pdo->prepare('SELECT q.position,q.question_text,q.topic,q.section_title,q.direction_text,q.correct_option,aa.answer_option,aa.is_correct,qo.position AS option_position,qo.option_text FROM attempts a JOIN attempt_answers aa ON aa.attempt_id=a.id JOIN questions q ON q.id=aa.question_id LEFT JOIN question_options qo ON qo.question_id=q.id WHERE a.id=:attempt_id AND a.user_id=:user_id ORDER BY q.position,qo.position');
        $stmt->execute([':attempt_id' => $attemptId, ':user_id' => $userId]);
        $questions = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $position = (int) $row['position'];
            if (!isset($questions[$position])) {
                $questions[$position] = [
                    'q' => (string) $row['question_text'],
                    'topic' => (string) ($row['topic'] ?? ''),
                    'section' => (string) ($row['section_title'] ?? ''),
                    'direction' => (string) ($row['direction_text'] ?? ''),
                    'answer' => (int) $row['correct_option'],
                    'selected' => $row['answer_option'] === null ? null : (int) $row['answer_option'],
                    'is_correct' => (bool) $row['is_correct'],
                    'options' => [],
                ];
            }
            if ($row['option_position'] !== null) $questions[$position]['options'][(int) $row['option_position']] = (string) $row['option_text'];
        }
        return $questions === [] ? null : array_values($questions);
    }

    if (is_array($attempt['review'] ?? null)) {
        return array_values(array_filter($attempt['review'], 'is_array'));
    }

    $slug = $sample ? 'sample' : (string) ($attempt['product'] ?? '');
    $testKey = (string) ($attempt['test_id'] ?? 'test-01');
    if ($slug === '' || preg_match('/^test-[0-9]+$/', $testKey) !== 1) return null;
    $test = test_record($slug, $testKey);
    if (!is_array($test) || !is_array($test['questions'] ?? null)) return null;

    $review = [];
    foreach (array_values($test['questions']) as $position => $question) {
        if (!is_array($question)) continue;
        $selected = $attempt['answers'][$position] ?? null;
        $correctAnswer = (int) ($question['answer'] ?? -1);
        $review[] = [
            'q' => (string) ($question['q'] ?? ''),
            'topic' => (string) ($question['topic'] ?? ''),
            'section' => (string) ($question['section'] ?? ''),
            'direction' => (string) ($question['direction'] ?? ''),
            'answer' => $correctAnswer,
            'selected' => $selected === null ? null : (int) $selected,
            'is_correct' => $selected !== null && (int) $selected === $correctAnswer,
            'options' => is_array($question['options'] ?? null) ? $question['options'] : [],
        ];
    }
    return $review === [] ? null : $review;
}
