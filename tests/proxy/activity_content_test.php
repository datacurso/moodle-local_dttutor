<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_dttutor\proxy;

/**
 * The course material that reaches the AI service, and above all the material that does not.
 *
 * See MDL-INT-016 of cases_data/dttutor/dttutor-2.0.9.md.
 *
 * @package    local_dttutor
 * @category   test
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_dttutor\proxy\activity_content
 */
final class activity_content_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // Creating activities reaches the file API, which refuses to work with nobody logged in.
        $this->setAdminUser();
    }

    /**
     * Allow the material of the course to travel.
     */
    private function enable_content(): void {
        set_config('include_content', 1, 'local_dttutor');
    }

    /**
     * A course with a student enrolled in it.
     *
     * @return array{0: \stdClass, 1: \stdClass}
     */
    private function course_with_a_student(): array {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_and_enrol($course, 'student');
        return [$course, $student];
    }

    /**
     * The knowledge the tutor receives for a student.
     *
     * @param \stdClass $course
     * @param \stdClass $student
     * @return string
     */
    private function knowledge(\stdClass $course, \stdClass $student): string {
        return context_preloader::build((int)$course->id, (int)$student->id);
    }

    /**
     * MDL-INT-016: no material travels until the administrator asks for it.
     */
    public function test_no_material_travels_by_default(): void {
        [$course, $student] = $this->course_with_a_student();
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Chapter one',
            'content' => 'The mitochondria is the powerhouse of the cell',
            'intro' => 'A short description',
        ]);

        $text = $this->knowledge($course, $student);

        $this->assertStringContainsString('Chapter one', $text);
        $this->assertStringNotContainsString('powerhouse', $text);
        $this->assertStringNotContainsString('A short description', $text);
    }

    /**
     * MDL-INT-016: with the setting on, the body of a page and its description travel.
     */
    public function test_the_body_of_a_page_travels(): void {
        $this->enable_content();
        [$course, $student] = $this->course_with_a_student();
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Chapter one',
            'content' => '<p>The mitochondria is the <b>powerhouse</b> of the cell</p>',
            'intro' => 'What this page covers',
        ]);

        $text = $this->knowledge($course, $student);

        $this->assertStringContainsString('What this page covers', $text);
        $this->assertStringContainsString('The mitochondria is the powerhouse of the cell', $text);
        $this->assertStringNotContainsString('<b>', $text);
    }

    /**
     * MDL-INT-016: the material reaches the model as it was written, not shouted.
     *
     * Turning HTML into text puts bold, headings and table headers in capitals, which the model
     * would read as emphasis the author never intended.
     */
    public function test_the_material_is_not_shouted(): void {
        $this->enable_content();
        [$course, $student] = $this->course_with_a_student();
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Styled page',
            'content' => '<h2>Photosynthesis</h2><p>It needs <strong>light</strong> and water.</p>'
                . '<table><tr><th>Stage</th></tr><tr><td>Light phase</td></tr></table>',
        ]);

        $text = $this->knowledge($course, $student);

        $this->assertStringContainsString('Photosynthesis', $text);
        $this->assertStringContainsString('light', $text);
        $this->assertStringContainsString('Stage', $text);
        $this->assertStringNotContainsString('PHOTOSYNTHESIS', $text);
        $this->assertStringNotContainsString('LIGHT', $text);
        $this->assertStringNotContainsString('STAGE', $text);
    }

    /**
     * MDL-INT-016: the description of any activity travels, whatever its type.
     */
    public function test_the_description_of_any_activity_travels(): void {
        $this->enable_content();
        [$course, $student] = $this->course_with_a_student();
        $this->getDataGenerator()->create_module('forum', [
            'course' => $course->id,
            'name' => 'Doubts',
            'intro' => 'Ask here about the second unit',
        ]);

        $this->assertStringContainsString('Ask here about the second unit', $this->knowledge($course, $student));
    }

    /**
     * MDL-INT-016: the instructions of an assignment travel.
     */
    public function test_the_instructions_of_an_assignment_travel(): void {
        $this->enable_content();
        [$course, $student] = $this->course_with_a_student();
        $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => 'Essay',
            'intro' => 'Write about the industrial revolution',
            'activityeditor' => [
                'text' => 'Use at least three sources and cite them',
                'format' => FORMAT_HTML,
                'itemid' => 0,
            ],
        ]);

        $text = $this->knowledge($course, $student);

        $this->assertStringContainsString('Write about the industrial revolution', $text);
        $this->assertStringContainsString('Use at least three sources', $text);
    }

    /**
     * MDL-INT-016: the address of a URL travels, so the tutor can point at it.
     */
    public function test_the_address_of_a_url_travels(): void {
        $this->enable_content();
        [$course, $student] = $this->course_with_a_student();
        $this->getDataGenerator()->create_module('url', [
            'course' => $course->id,
            'name' => 'Reference',
            'externalurl' => 'https://example.com/reference',
        ]);

        $this->assertStringContainsString('https://example.com/reference', $this->knowledge($course, $student));
    }

    /**
     * MDL-INT-016: the visible chapters of a book travel, and a hidden chapter does not.
     */
    public function test_the_visible_chapters_of_a_book_travel(): void {
        $this->enable_content();
        [$course, $student] = $this->course_with_a_student();
        $book = $this->getDataGenerator()->create_module('book', ['course' => $course->id, 'name' => 'Handbook']);
        $bookgenerator = $this->getDataGenerator()->get_plugin_generator('mod_book');
        $bookgenerator->create_chapter([
            'bookid' => $book->id,
            'title' => 'Visible chapter',
            'content' => 'Photosynthesis turns light into sugar',
        ]);
        $bookgenerator->create_chapter([
            'bookid' => $book->id,
            'title' => 'Draft chapter',
            'content' => 'Unfinished notes nobody should read',
            'hidden' => 1,
        ]);

        $text = $this->knowledge($course, $student);

        $this->assertStringContainsString('Visible chapter', $text);
        $this->assertStringContainsString('Photosynthesis turns light into sugar', $text);
        $this->assertStringNotContainsString('Draft chapter', $text);
        $this->assertStringNotContainsString('Unfinished notes', $text);
    }

    /**
     * MDL-INT-016: what the people taking the course write in a forum never travels.
     */
    public function test_forum_posts_never_travel(): void {
        $this->enable_content();
        [$course, $student] = $this->course_with_a_student();
        $forum = $this->getDataGenerator()->create_module('forum', [
            'course' => $course->id,
            'name' => 'Doubts',
            'intro' => 'Ask here',
        ]);
        $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion([
            'course' => $course->id,
            'forum' => $forum->id,
            'userid' => $student->id,
            'name' => 'My private doubt',
            'message' => 'I did not understand anything about the exam',
        ]);

        $text = $this->knowledge($course, $student);

        $this->assertStringContainsString('Ask here', $text);
        $this->assertStringNotContainsString('My private doubt', $text);
        $this->assertStringNotContainsString('I did not understand anything', $text);
    }

    /**
     * MDL-INT-016: what the people taking the course write in a glossary never travels.
     */
    public function test_glossary_entries_never_travel(): void {
        $this->enable_content();
        [$course, $student] = $this->course_with_a_student();
        $glossary = $this->getDataGenerator()->create_module('glossary', [
            'course' => $course->id,
            'name' => 'Terms',
            'intro' => 'Shared terms',
        ]);
        $this->getDataGenerator()->get_plugin_generator('mod_glossary')->create_content(
            $glossary,
            ['concept' => 'Osmosis', 'definition' => 'A definition written by a classmate', 'userid' => $student->id]
        );

        $text = $this->knowledge($course, $student);

        $this->assertStringContainsString('Shared terms', $text);
        $this->assertStringNotContainsString('written by a classmate', $text);
    }

    /**
     * MDL-INT-016: the questions of a quiz never travel.
     */
    public function test_quiz_questions_never_travel(): void {
        global $CFG;
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $this->enable_content();
        [$course, $student] = $this->course_with_a_student();
        $quiz = $this->getDataGenerator()->create_module('quiz', [
            'course' => $course->id,
            'name' => 'Final quiz',
            'intro' => 'Covers units one to three',
        ]);

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('truefalse', null, [
            'category' => $category->id,
            'name' => 'Leaked question',
            'questiontext' => 'The answer to the exam question is forty two',
        ]);
        quiz_add_quiz_question($question->id, $quiz);

        $text = $this->knowledge($course, $student);

        $this->assertStringContainsString('Covers units one to three', $text);
        $this->assertStringNotContainsString('Leaked question', $text);
        $this->assertStringNotContainsString('forty two', $text);
    }

    /**
     * MDL-INT-016: what a student hands in never travels.
     */
    public function test_submissions_never_travel(): void {
        global $DB;

        $this->enable_content();
        [$course, $student] = $this->course_with_a_student();
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => 'Essay',
            'intro' => 'Write about the industrial revolution',
        ]);
        $submission = $DB->insert_record('assign_submission', (object)[
            'assignment' => $assign->id,
            'userid' => $student->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'status' => 'submitted',
            'attemptnumber' => 0,
            'latest' => 1,
        ]);
        $DB->insert_record('assignsubmission_onlinetext', (object)[
            'assignment' => $assign->id,
            'submission' => $submission,
            'onlinetext' => 'The essay I wrote and nobody else should read',
            'onlineformat' => FORMAT_HTML,
        ]);

        $text = $this->knowledge($course, $student);

        $this->assertStringContainsString('Write about the industrial revolution', $text);
        $this->assertStringNotContainsString('The essay I wrote', $text);
    }

    /**
     * MDL-INT-016: the material of an activity hidden from the student never travels.
     */
    public function test_the_material_of_a_hidden_activity_never_travels(): void {
        $this->enable_content();
        [$course, $student] = $this->course_with_a_student();
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Hidden page',
            'content' => 'Material that is not published yet',
            'visible' => 0,
        ]);

        $text = $this->knowledge($course, $student);

        $this->assertStringNotContainsString('Hidden page', $text);
        $this->assertStringNotContainsString('not published yet', $text);
    }

    /**
     * MDL-INT-016: an activity the student cannot open yet gives up its name, never its material.
     */
    public function test_the_material_of_a_locked_activity_never_travels(): void {
        global $CFG;
        $this->enable_content();
        $CFG->enableavailability = 1;
        [$course, $student] = $this->course_with_a_student();
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Locked page',
            'content' => 'Material behind a restriction',
            'intro' => 'A description behind a restriction',
            'availability' => json_encode((object)[
                'op' => '&',
                'c' => [(object)['type' => 'date', 'd' => '>=', 't' => time() + WEEKSECS]],
                'showc' => [true],
            ]),
        ]);

        $text = $this->knowledge($course, $student);

        $this->assertStringContainsString('Locked page', $text);
        $this->assertStringContainsString('not available yet:', $text);
        $this->assertStringNotContainsString('Material behind a restriction', $text);
        $this->assertStringNotContainsString('A description behind a restriction', $text);
    }

    /**
     * MDL-INT-016: a long text is cut, and the tutor is told that it is reading an extract.
     */
    public function test_a_long_text_is_cut_and_announced(): void {
        $this->enable_content();
        set_config('content_chars_per_activity', 200, 'local_dttutor');
        [$course, $student] = $this->course_with_a_student();
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Long page',
            'content' => str_repeat('sugar ', 200),
        ]);

        $text = $this->knowledge($course, $student);

        $this->assertStringContainsString(activity_content::TRUNCATION_MARK, $text);
        $this->assertStringContainsString('abridged', $text);
        $this->assertLessThan(200, substr_count($text, 'sugar'));
    }

    /**
     * MDL-INT-016: the material of a whole question is bounded, however many activities there are.
     */
    public function test_the_material_of_a_question_is_bounded(): void {
        $this->enable_content();
        set_config('content_chars_per_activity', 500, 'local_dttutor');
        set_config('content_chars_total', 1000, 'local_dttutor');
        [$course, $student] = $this->course_with_a_student();
        for ($i = 1; $i <= 6; $i++) {
            $this->getDataGenerator()->create_module('page', [
                'course' => $course->id,
                'name' => 'Page ' . $i,
                'content' => str_repeat('word' . $i . ' ', 200),
            ]);
        }

        $text = $this->knowledge($course, $student);

        // Every activity is still listed; only their material is bounded.
        for ($i = 1; $i <= 6; $i++) {
            $this->assertStringContainsString('Page ' . $i, $text);
        }
        $this->assertStringNotContainsString('word6', $text);
        $this->assertStringContainsString('abridged', $text);
    }

    /**
     * MDL-INT-016: only the activities whose text is written by the teaching side give up a body.
     *
     * The guarantee of this feature rests on that list, so it is checked on its own: a module
     * joins it only after someone has looked at where its text comes from.
     */
    public function test_only_reviewed_activities_give_up_a_body(): void {
        $this->assertEqualsCanonicalizing(
            ['page', 'book', 'assign', 'url'],
            activity_content::READABLE_BODY
        );
    }

    /**
     * MDL-INT-016: a corrected chapter reaches the next question, with no cache to clear.
     *
     * Editing an activity rebuilds the cache of the course, but the chapters of a book are edited
     * by their own pages and do not, so the material is watched separately.
     */
    public function test_a_corrected_chapter_reaches_the_next_question(): void {
        global $DB;
        $this->enable_content();
        [$course, $student] = $this->course_with_a_student();
        $book = $this->getDataGenerator()->create_module('book', ['course' => $course->id, 'name' => 'Handbook']);
        $chapter = $this->getDataGenerator()->get_plugin_generator('mod_book')->create_chapter([
            'bookid' => $book->id,
            'title' => 'Chapter',
            'content' => 'Water boils at ninety degrees',
        ]);

        $this->assertStringContainsString('ninety degrees', $this->knowledge($course, $student));

        $DB->update_record('book_chapters', (object)[
            'id' => $chapter->id,
            'content' => 'Water boils at one hundred degrees',
            'timemodified' => time() + 1,
        ]);

        $text = $this->knowledge($course, $student);
        $this->assertStringContainsString('one hundred degrees', $text);
        $this->assertStringNotContainsString('ninety degrees', $text);
    }
}
