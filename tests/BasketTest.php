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
use Docalist\UserData\Basket;

class BasketTest extends WP_UnitTestCase
{
    public function setup()
    {
        wp_set_current_user(1);
        delete_user_meta(get_current_user_id(), 'docalist-basket-test');
    }

    public function testNew()
    {
        $o = new Basket('test', 1);

        $this->assertSame($o->type(), 'basket');
        $this->assertSame($o->name(), 'test');
        $this->assertSame($o->id(), 'docalist-basket-test');
    }

    public function testAdd()
    {
        $o = new Basket('test', 1);

        $o->add(10);
        $this->assertSame($o->data(), [10]);
        $this->assertTrue($o->isModified());

        $o->add(20);
        $this->assertSame($o->data(), [10, 20]);
        $this->assertTrue($o->isModified());

        $o->add([5, 15, 25]);
        $this->assertSame($o->data(), [10, 20, 5, 15, 25]);
        $this->assertTrue($o->isModified());

        $o->add([]);
        $this->assertSame($o->data(), [10, 20, 5, 15, 25]);
        $this->assertTrue($o->isModified());
    }

    public function testRemove()
    {
        $o = new Basket('test', 1);

        $o->remove(10);
        $this->assertFalse($o->isModified());

        $o->add([5, 15, 25]);

        $o->remove(15);
        $this->assertTrue($o->isModified());
        $this->assertSame($o->data(), [5, 25]);
    }

    public function testSave()
    {
        $o = new Basket('test', 1);

        $o->add([5, 15, 25])->save();

        $o = new Basket('test', 1);
        $this->assertSame($o->data(), [5, 15, 25]);

        $o->clear()->save();
    }
}
