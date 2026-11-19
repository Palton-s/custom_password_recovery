<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_custom_password_recovery;

/**
 * Tests for the manager class.
 *
 * @package    local_custom_password_recovery
 * @copyright  2026 CEAD/UnB
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_custom_password_recovery\manager
 */
class manager_test extends \advanced_testcase {

    /** @var string A valid CPF used as username. */
    const CPF = '52998224725';

    protected function setUp(): void {
        parent::setUp();
        \cache::make(manager::COMPONENT, 'ratelimit')->purge();
    }

    /**
     * Creates the secondary e-mail profile field and a user with both addresses.
     *
     * @param string $secondary
     * @return \stdClass
     */
    protected function create_user_with_secondary(string $secondary = 'pessoal@example.com'): \stdClass {
        global $DB;
        $field = $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text', 'shortname' => 'email_2aopcao', 'name' => 'E-mail 2ª opção',
        ]);
        $user = $this->getDataGenerator()->create_user([
            'username' => self::CPF, 'email' => 'aluno@unb.br', 'password' => 'Original#123',
        ]);
        $DB->insert_record('user_info_data', (object)[
            'userid' => $user->id, 'fieldid' => $field->id, 'data' => $secondary, 'dataformat' => 0,
        ]);
        set_config('secondaryemailfield', 'email_2aopcao', manager::COMPONENT);
        return $user;
    }

    public function test_is_valid_cpf(): void {
        $this->assertTrue(manager::is_valid_cpf('52998224725'));
        $this->assertFalse(manager::is_valid_cpf('52998224724'));
        $this->assertFalse(manager::is_valid_cpf('11111111111'));
        $this->assertFalse(manager::is_valid_cpf('123'));
    }

    public function test_normalise_identifier(): void {
        $this->assertSame('52998224725', manager::normalise_identifier(' 529.982.247-25 ', manager::MODE_USERNAME));
        $this->assertSame('admin', manager::normalise_identifier('Admin', manager::MODE_USERNAME));
        $this->assertNull(manager::normalise_identifier('admin', manager::MODE_CPF));
        $this->assertNull(manager::normalise_identifier('52998224724', manager::MODE_CPF));
        $this->assertSame('52998224725', manager::normalise_identifier('529.982.247-25', manager::MODE_CPF));
        $this->assertNull(manager::normalise_identifier('   ', manager::MODE_USERNAME));
    }

    public function test_mask_email(): void {
        $this->assertSame('jo*****@unb.br', manager::mask_email('joaosilva@unb.br'));
        $this->assertSame('a*****@x.com', manager::mask_email('ab@x.com'));
        $this->assertSame('a*****@x.com', manager::mask_email('a@x.com'));
    }

    public function test_link_sent_to_both_addresses_without_changing_password(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->create_user_with_secondary();
        $oldhash = $DB->get_field('user', 'password', ['id' => $user->id]);

        $sink = $this->redirectEmails();
        $result = manager::process_request('529.982.247-25', '10.0.0.1');
        $messages = $sink->get_messages();
        $sink->close();

        $this->assertSame(manager::STATUS_SENT, $result['status']);
        $this->assertSame(['al*****@unb.br', 'pe*****@example.com'], $result['emails']);
        $this->assertCount(2, $messages);
        $this->assertEqualsCanonicalizing(['aluno@unb.br', 'pessoal@example.com'], array_column($messages, 'to'));

        $reset = $DB->get_record('user_password_resets', ['userid' => $user->id], '*', MUST_EXIST);
        $body = quoted_printable_decode($messages[0]->body);
        $this->assertStringContainsString('/local/custom_password_recovery/reset.php?token=' . $reset->token, $body);
        $this->assertStringNotContainsString('Original#123', $body);

        // The password itself was not touched.
        $this->assertSame($oldhash, $DB->get_field('user', 'password', ['id' => $user->id]));
    }

    public function test_new_request_invalidates_previous_token(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->create_user_with_secondary();
        $sink = $this->redirectEmails();
        manager::process_request(self::CPF, '10.0.0.1');
        $first = $DB->get_field('user_password_resets', 'token', ['userid' => $user->id]);
        manager::process_request(self::CPF, '10.0.0.1');
        $sink->close();

        $tokens = $DB->get_fieldset_select('user_password_resets', 'token', 'userid = ?', [$user->id]);
        $this->assertCount(1, $tokens);
        $this->assertNotSame($first, $tokens[0]);
    }

    public function test_duplicate_and_invalid_secondary_email_is_ignored(): void {
        $this->resetAfterTest();
        $this->create_user_with_secondary('ALUNO@unb.br');
        $sink = $this->redirectEmails();
        $result = manager::process_request(self::CPF, '10.0.0.1');
        $this->assertCount(1, $sink->get_messages());
        $sink->close();
        $this->assertSame(['al*****@unb.br'], $result['emails']);
    }

    public function test_unknown_and_blocked_accounts(): void {
        $this->resetAfterTest();
        $sink = $this->redirectEmails();

        $this->assertSame(manager::STATUS_NOTFOUND, manager::process_request('00000000000', '10.0.0.1')['status']);

        $this->getDataGenerator()->create_user(['username' => 'suspenso', 'suspended' => 1]);
        $this->assertSame(manager::STATUS_CANNOTRESET, manager::process_request('suspenso', '10.0.0.1')['status']);

        $this->getDataGenerator()->create_user(['username' => 'semlogin', 'auth' => 'nologin']);
        $this->assertSame(manager::STATUS_CANNOTRESET, manager::process_request('semlogin', '10.0.0.1')['status']);

        $this->assertCount(0, $sink->get_messages());
        $sink->close();
    }

    public function test_rate_limits(): void {
        $this->resetAfterTest();
        $this->create_user_with_secondary();
        set_config('maxperaccount', 2, manager::COMPONENT);
        set_config('maxperip', 5, manager::COMPONENT);
        $sink = $this->redirectEmails();

        $this->assertSame(manager::STATUS_SENT, manager::process_request(self::CPF, '10.0.0.1')['status']);
        $this->assertSame(manager::STATUS_SENT, manager::process_request(self::CPF, '10.0.0.2')['status']);
        // Account limit reached, even from another IP.
        $this->assertSame(manager::STATUS_RATELIMITED, manager::process_request(self::CPF, '10.0.0.3')['status']);

        // IP limit: unknown identifiers count too.
        for ($i = 0; $i < 5; $i++) {
            manager::process_request('naoexiste' . $i, '10.0.0.9');
        }
        $this->assertSame(manager::STATUS_RATELIMITED, manager::process_request('naoexiste', '10.0.0.9')['status']);

        $sink->close();
    }

    public function test_event_triggered(): void {
        $this->resetAfterTest();
        $user = $this->create_user_with_secondary();
        $sink = $this->redirectEmails();
        $events = $this->redirectEvents();
        manager::process_request(self::CPF, '10.0.0.1');
        $sink->close();

        $triggered = array_values(array_filter($events->get_events(), function($e) {
            return $e instanceof event\recovery_link_sent;
        }));
        $this->assertCount(1, $triggered);
        $this->assertEquals($user->id, $triggered[0]->relateduserid);
        $this->assertEquals(2, $triggered[0]->other['emailcount']);
    }
}
