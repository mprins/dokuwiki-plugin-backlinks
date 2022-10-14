<?php
/*
 * Copyright (c) 2016 Mark C. Prins <mprins@users.sf.net>
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

/**
 * Syntax tests for the backlinks plugin.
 *
 * @group plugin_backlinks
 * @group plugins
 */
class syntax_include_plugin_backlinks_test extends DokuWikiTest {

    protected $pluginsEnabled = array('backlinks');

    /**
     * copy data.
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        global $conf;
        $conf['allowdebug'] = 1;

        TestUtils::rcopy(TMP_DIR, dirname(__FILE__) . '/data/');

        dbglog("\nset up class syntax_plugin_backlinks_test");
    }

    public function setUp(): void {
        parent::setUp();

        global $conf;
        $conf['allowdebug'] = 1;
        $conf['cachetime']  = -1;

        $data = array();
        search($data, $conf['datadir'], 'search_allpages', array('skipacl' => true));

        //dbglog($data, "pages for indexing");

        $verbose = false;
        $force   = false;
        foreach($data as $val) {
            idx_addPage($val['id'], $verbose, $force);
        }

        if($conf['allowdebug']) {
            touch(DOKU_TMP_DATA . 'cache/debug.log');
        }
    }

    public function tearDown(): void {
        parent::tearDown();

        global $conf;
        // try to get the debug log after running the test, print and clear
        if($conf['allowdebug']) {
            print "\n";
            readfile(DOKU_TMP_DATA . 'cache/debug.log');
            unlink(DOKU_TMP_DATA . 'cache/debug.log');
        }
    }

    public function testInclude(): void {
        $request  = new TestRequest();
        $response = $request->get(array('id' => 'backlinks_include_syntax'), '/doku.php');

        $this->assertTrue(
            strpos($response->getContent(), 'Backlinks to what Bob Ross says (including only)') !== false,
            '"Backlinks to what Bob Ross says (including only)" was not in the output'
        );

        $doc = phpQuery::newDocument($response->getContent());
        // look for id="plugin__backlinks"
        $this->assertEquals(
            1,
            pq('#plugin__backlinks', $doc)->length,
            'There should be one backlinks element'
        );

        $wikilinks = pq('#plugin__backlinks ul li', $doc);
        dbglog($wikilinks->text(), 'found backlinks');
        $this->assertEquals(
            1,
            $wikilinks->contents()->length,
            'There should be 1 backlink'
        );

        $lastlink = pq('a:last', $wikilinks);
        dbglog($lastlink->text(), "last backlink");
        $this->assertEquals(
            $lastlink->text(),
            'An included link to Bob Ross',
            'The last backlink should be "An included link to Bob Ross"'
        );
    }
}
