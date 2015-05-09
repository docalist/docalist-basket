<?php
/**
 * This file is part of the "Docalist Biblio UserData" plugin.
 *
 * Copyright (C) 2015-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Tests\Biblio\UserData
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Docalist\Tests\Biblio\UserData;

use WP_UnitTestCase;

use Docalist\Biblio\UserData\UserDataObject;

class UserDataObjectTest extends WP_UnitTestCase {
    public function setup() {
        wp_set_current_user(1);
        delete_user_meta(get_current_user_id(), 'docalist-object-test');
    }

    public function testNew() {
        $o = new UserDataObject('object', 'test', 1);

        $this->assertSame($o->type(), 'object');
        $this->assertSame($o->name(), 'test');
        $this->assertSame($o->id(), 'docalist-object-test');
        $this->assertFalse($o->isModified());
        $this->assertSame($o->count(), 0);
        $this->assertFalse($o->has(10));
        $this->assertNull($o->get(10));
        $this->assertSame($o->data(), []);
    }

    public function testGetSet() {
        $o = new UserDataObject('object', 'test', 1);

        $o->set(10, 'dix');
        $this->assertTrue($o->isModified());
        $this->assertSame($o->count(), 1);
        $this->assertTrue($o->has(10));
        $this->assertSame($o->get(10), 'dix');
        $this->assertSame($o->data(), ['10' => 'dix']);

        $o->set(10, 'DIX');
        $this->assertTrue($o->isModified());
        $this->assertSame($o->count(), 1);
        $this->assertTrue($o->has(10));
        $this->assertSame($o->get(10), 'DIX');
        $this->assertSame($o->data(), ['10' => 'DIX']);

        $o->set(10, 'dix');
        $o->set(15, 'quinze');
        $o->set(20, 'vingt');
        $this->assertTrue($o->isModified());
        $this->assertSame($o->count(), 3);
        $this->assertTrue($o->has(10));
        $this->assertTrue($o->has(15));
        $this->assertTrue($o->has(20));
        $this->assertSame($o->get(10), 'dix');
        $this->assertSame($o->get(15), 'quinze');
        $this->assertSame($o->get(20), 'vingt');
        $this->assertSame($o->data(), ['10' => 'dix', '15' => 'quinze', '20' => 'vingt']);
    }

    public function testClear() {
        $o = new UserDataObject('object', 'test', 1);

        $o->set(10, 'dix');
        $o->set(15, 'quinze');
        $o->set(20, 'vingt');

        $o->clear(15);
        $this->assertTrue($o->isModified());
        $this->assertSame($o->count(), 2);
        $this->assertFalse($o->has(15));
        $this->assertNull($o->get(15));
        $this->assertSame($o->data(), ['10' => 'dix', '20' => 'vingt']);

        $o->clear();
        $this->assertTrue($o->isModified());
        $this->assertSame($o->count(), 0);
        $this->assertFalse($o->has(10));
        $this->assertNull($o->get(10));
        $this->assertSame($o->data(), []);

    }

    public function testClearOnEmpty() {
        $o = new UserDataObject('object', 'test', 1);

        $o->clear(15);
        $this->assertFalse($o->isModified());
        $this->assertSame($o->count(), 0);
        $this->assertFalse($o->has(15));
        $this->assertNull($o->get(15));
        $this->assertSame($o->data(), []);

        $o->clear();
        $this->assertFalse($o->isModified());
        $this->assertSame($o->count(), 0);
        $this->assertFalse($o->has(10));
        $this->assertNull($o->get(10));
        $this->assertSame($o->data(), []);
    }

    public function testLoadSave() {
        $o = new UserDataObject('object', 'test', 1);

        // enregistre un objet vide : le meta n'est pas créé
//         $o->save();
//         $this->assertSame('', get_user_meta(get_current_user_id(), 'docalist-object-test'));

        // enregistrement normal
        $o->set(10, 'dix');
        $o->set(15, 'quinze');
        $o->set(20, 'vingt');
        $o->save();
        $this->assertFalse($o->isModified());

        unset($o);

        // Rechargement et vérif
        $o = new UserDataObject('object', 'test', 1);

        $this->assertFalse($o->isModified());
        $this->assertSame($o->count(), 3);
        $this->assertTrue($o->has(15));
        $this->assertSame($o->get(15), 'quinze');
        $this->assertSame($o->data(), ['10' => 'dix', '15' => 'quinze', '20' => 'vingt']);

        // Modif d'un élément, enregistrement et vérif
        $o->clear(15)->save();
        unset($o);
        $o = new UserDataObject('object', 'test', 1);
        $this->assertSame($o->count(), 2);
        $this->assertSame($o->data(), ['10' => 'dix', '20' => 'vingt']);

        // Clear, save et vérif que le meta est supprimé
        unset($o);
        $o = new UserDataObject('object', 'test', 1);
        $o->clear()->save();
        $this->assertSame('', get_user_meta(get_current_user_id(), 'docalist-object-test', true));
        unset($o);
        $o = new UserDataObject('object', 'test', 1);
        $this->assertSame($o->count(), 0);
    }
}