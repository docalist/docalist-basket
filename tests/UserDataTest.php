<?php declare(strict_types=1);
/**
 * This file is part of Docalist UserData.
 *
 * Copyright (C) 2015-2018 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 *
 * @author Daniel Ménard <daniel.menard@laposte.net>
 */
namespace Docalist\Tests\UserData;

use WP_UnitTestCase;
use Docalist\UserData\UserData;

class UserDataTest extends WP_UnitTestCase
{
    public function setup()
    {
        wp_set_current_user(1);
        delete_user_meta(get_current_user_id(), 'docalist-object-test');
    }

    public function testInstance()
    {
        $o = new UserData();

        $this->assertSame($o->baskets(), []);

        $b1 = $o->basket('un');
        $this->assertSame($b1, $o->basket('un'));
    }

    public function testBaskets()
    {
        $o = new UserData();

        $this->assertSame($o->baskets(), []);

        $o->basket('un')->add([10, 11, 12])->save();
        $o->basket('deux')->add([20, 21, 22])->save();
        $o->basket('trois')->add([30, 31, 32])->save();

        $this->assertSame($o->baskets(), ['un', 'deux', 'trois']);

        $o->basket('deux')->clear()->save();
        $this->assertSame($o->baskets(), ['un', 'trois']);

        $o->basket('un')->clear()->save();
        $o->basket('trois')->clear()->save();
        $this->assertSame($o->baskets(), []);
    }
}
