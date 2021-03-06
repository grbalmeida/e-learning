<?php

namespace Models;

use \Core\Model;

class Lesson extends Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getLessonsByModule(int $id): array
    {
        $array = [];

        $sql = 'SELECT id, module_id, course_id, type, lesson_order
                FROM lessons
                WHERE module_id = :id
                ORDER BY lesson_order';
        $sql = $this->database->prepare($sql);
        $sql->bindValue(':id', $id);
        $sql->execute();

        if ($sql->rowCount() > 0) {
            $array = $sql->fetchAll(\PDO::FETCH_ASSOC);

            foreach($array as $key => $lesson) {
                if ($lesson['type'] == 'video') {
                    $sql = 'SELECT name
                            FROM videos
                            WHERE lesson_id = :lesson_id';
                    $sql = $this->database->prepare($sql);
                    $sql->bindValue(':lesson_id', $lesson['id']);
                    $sql->execute();

                    $array[$key]['name'] = $sql->fetch(\PDO::FETCH_ASSOC)['name'];
                } else if ($lesson['type'] == 'questionnaire') {
                    $array[$key]['name'] = 'Questionário';
                }
            }
        }
        return $array;
    }

    public function delete(int $lesson_id): void
    {
        // ON DELETE

        $queries = [
            'DELETE FROM questionnaires WHERE lesson_id = ?',
            'DELETE FROM videos WHERE lesson_id = ?',
            'DELETE FROM historic WHERE lesson_id = ?',
            'DELETE FROM lessons WHERE id = ?'
        ];

        foreach($queries as $query) {
            $sql = $this->database->prepare($query);
            $sql->execute([$lesson_id]);
        }
    }

    public function add(int $course_id, string $name, int $module, string $type): void
    {
        $sql = 'SELECT MAX(lesson_order) AS lesson_order
                FROM lessons
                WHERE module_id = :module_id';
        $sql = $this->database->prepare($sql);
        $sql->bindValue(':module_id', $module);
        $sql->execute();

        $lesson_order = 1;

        if ($sql->rowCount() > 0) {
            $lesson_order += intval($sql->fetch(\PDO::FETCH_ASSOC)['lesson_order']);
        }

        $sql = 'INSERT INTO lessons
                    (module_id, course_id, lesson_order, type)
                VALUES
                    (:module_id, :course_id, :lesson_order, :type)';
        $sql = $this->database->prepare($sql);
        $sql->bindValue(':module_id', $module);
        $sql->bindValue(':course_id', $course_id);
        $sql->bindValue(':lesson_order', $lesson_order);
        $sql->bindValue(':type', $type);
        $sql->execute();

        $lesson_id = $this->database->lastInsertId();

        if ($type == 'video') {
            $sql = 'INSERT INTO videos
                        (lesson_id, name)
                    VALUES
                        (?, ?)';
            $sql = $this->database->prepare($sql);
            $sql->execute([$lesson_id, $name]);
        } else {
            $sql = 'INSERT INTO questionnaires
                        (lesson_id)
                    VALUES
                        (?)';
            $sql = $this->database->prepare($sql);
            $sql->execute([$lesson_id]);
        }
    }

    public function getLesson($lesson_id): array
    {
        $array = [];

        $sql = 'SELECT type
                FROM lessons
                WHERE id = :lesson_id';
        $sql = $this->database->prepare($sql);
        $sql->bindValue(':lesson_id', $lesson_id);
        $sql->execute();

        if ($sql->rowCount() > 0) {
            $row = $sql->fetch(\PDO::FETCH_ASSOC);

            if ($row['type'] == 'video') {
                $sql = 'SELECT id, lesson_id, name, description, url
                        FROM videos
                        WHERE lesson_id = :lesson_id';
                $sql = $this->database->prepare($sql);
                $sql->bindValue(':lesson_id', $lesson_id);
                $sql->execute();
                $array = $sql->fetch(\PDO::FETCH_ASSOC);
                $array['type'] = 'video';
            } else if ($row['type'] == 'questionnaire') {
                $sql = 'SELECT id, lesson_id, question, option1, option2, option3, option4, answer
                        FROM questionnaires
                        WHERE lesson_id = :lesson_id';
                $sql = $this->database->prepare($sql);
                $sql->bindValue(':lesson_id', $lesson_id);
                $sql->execute();
                $array = $sql->fetch(\PDO::FETCH_ASSOC);
                $array['type'] = 'questionnaire';
            }
        }

        return $array;
    }

    public function updateVideoLesson(string $name, 
                           string $description, 
                           string $url, 
                           int $lesson_id): void
    {
        $sql = 'UPDATE videos
                SET name = :name, description = :description, url = :url
                WHERE lesson_id = :lesson_id';
        $sql = $this->database->prepare($sql);
        $sql->bindValue(':name', $name);
        $sql->bindValue(':description', $description);
        $sql->bindValue(':url', $url);
        $sql->bindValue(':lesson_id', $lesson_id);
        $sql->execute();
    }

    public function updateQuestionnaireLesson(string $question,
                                              string $option1,
                                              string $option2,
                                              string $option3,
                                              string $option4,
                                              string $answer,
                                              int $lesson_id): void
    {
        $params = func_get_args();
        $sql = 'UPDATE questionnaires
                SET question = ?,
                    option1 = ?,
                    option2 = ?,
                    option3 = ?,
                    option4 = ?,
                    answer = ?
                WHERE lesson_id = ?';
        $sql = $this->database->prepare($sql);
        $sql->execute($params);
    }
}