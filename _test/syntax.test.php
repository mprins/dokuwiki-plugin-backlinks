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

use dokuwiki\Logger;

/**
 * Syntax tests for the backlinks plugin.
 *
 * @group plugin_backlinks
 * @group plugins
 */
class syntax_plugin_backlinks_test extends DokuWikiTest
{

    protected $pluginsEnabled = array('backlinks');

    /**
     * copy data and add pages to the index.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        global $conf;
        $conf['allowdebug'] = 1;

        TestUtils::rcopy(TMP_DIR, dirname(__FILE__) . '/data/');

        Logger::debug("set up class syntax_plugin_backlinks_test");
    }

    public function setUp(): void
    {
        parent::setUp();

        global $conf;
        $conf['allowdebug'] = 1;
        $conf['cachetime'] = -1;
        $verbose = false;
        $force = false;

        $data = array();
        search($data, $conf['datadir'], 'search_allpages', array('skipacl' => true));

        foreach ($data as $val) {
            (new Indexer())->addPage($val['id'], $verbose, $force);
        }

        if ($conf['allowdebug']) {
            touch(DOKU_TMP_DATA . 'cache/debug.log');
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();

        global $conf;
        // try to get the debug log after running the test, print and clear
        if ($conf['allowdebug']) {
            print "\n";
            readfile(DOKU_TMP_DATA . 'cache/debug.log');
            unlink(DOKU_TMP_DATA . 'cache/debug.log');
        }
    }

    public function testIndex(): void
    {
        $query = array('ross');
        $this->assertEquals(
            array(
                'ross' => array(
                    'link' => '3',
                    'bob_ross_says' => '1',
                    'backlinks_syntax' => '2',
                    'backlinks_include_syntax' => '2',
                    'backlinks_exclude_syntax' => '2',
                    'backlink_test_pages' => '8',
                    'include:link' => '3',
                    'exclude:link' => '3'
                )
            ),
            idx_lookup($query)
        );
    }

    public function testLinksPage(): void
    {
        $request = new TestRequest();
        $response = $request->get(array('id' => 'link'), '/doku.php');

        $this->assertTrue(
            str_contains($response->getContent(), 'A link to Bob Ross'),
            '"A link to Bob Ross" was not in the output'
        );
    }

    public function testStoryPage(): void
    {
        $request = new TestRequest();
        $response = $request->get(array('id' => 'bob_ross_says'), '/doku.php');

        $this->assertTrue(
            str_contains($response->getContent(), 'Bob Ross says'),
            '"Bob Ross says" was not in the output'
        );
    }

    public function testBacklinks(): void
    {
        $request = new TestRequest();
        $response = $request->get(array('id' => 'backlinks_syntax'), '/doku.php');

        $this->assertTrue(
            str_contains($response->getContent(), 'Backlinks to what Bob Ross says'),
            '"Backlinks to what Bob Ross says" was not in the output'
        );

        $doc = (new DOMWrap\Document())->loadHTML($response->getContent());
        // look for id="plugin__backlinks"
        $this->assertEquals(
            1,
            pq('#plugin__backlinks', $doc)->length,
            'There should be one backlinks element'
        );

        $wikilinks = pq('#plugin__backlinks ul li', $doc);
        Logger::debug('found backlinks', $wikilinks->text());
        $this->assertEquals(
            4,
            $wikilinks->contents()->length,
            'There should be 4 backlinks'
        );

        $lastlink = pq('a:last', $wikilinks);
        Logger::debug("last backlink", $lastlink->text());
        $this->assertEquals(
            'A link to Bob Ross',
            $lastlink->text(),
            'The last backlink should be a link to Bob Ross'
        );
    }
}
