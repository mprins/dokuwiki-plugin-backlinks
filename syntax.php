<?php
/**
 * DokuWiki Syntax Plugin Backlinks.
 *
 * Shows a list of pages that link back to a given page.
 *
 * Syntax:  {{backlinks>[pagename][#filterNS|!#filterNS]}}
 *
 *   [pagename] - a valid wiki pagename or a . for the current page
 *   [filterNS] - a valid,absolute namespace name, optionally prepended with ! to exclude
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Michael Klier <chi@chimeric.de>
 * @author  Mark C. Prins <mprins@users.sf.net>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_backlinks extends DokuWiki_Syntax_Plugin {
    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType() { return 'substition'; }

    /**
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType() { return 'block'; }

    /**
     * @see Doku_Parser_Mode::getSort()
     */
    function getSort() { return 304; }

    /**
     * Connect pattern to lexer.
     * @see Doku_Parser_Mode::connectTo()
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{backlinks>.+?\}\}', $mode, 'plugin_backlinks');
    }

    /**
     * Handler to prepare matched data for the rendering process.
     * @see DokuWiki_Syntax_Plugin::handle()
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {
        global $INFO;
        global $ID;

        // strip {{backlinks> from start and }} from end
        $match = substr($match, 12, -2);

        // Take the id of the source
        // It can be a rendering of a sidebar
	if ($ID == $INFO['id']) {
	    if (strstr($match, "#")) {
		$includeNS = substr(strstr($match, "#", FALSE), 1);
		$match = strstr($match, "#", TRUE);
	    }

	    $match = ($match == '.') ? $id : $match;

	    if (strstr($match, ".:")) {
		resolve_pageid(getNS($id), $match, $exists);
	    }
	}

        return (array($match, $includeNS));
    }

    /**
     * Handles the actual output creation.
     * @see DokuWiki_Syntax_Plugin::render()
     */
    function render($mode, Doku_Renderer $renderer, $data) {
        global $lang;
	global $ID;
	global $INFO;

	if ($mode == 'xhtml') {
            $renderer->info['cache'] = false;

            @require_once(DOKU_INC.'inc/fulltext.php');
	    $page_id = $data[0];
	    if($page_id == '.') {
		$page_id = $INFO['id'];
	    }
            $backlinks = ft_backlinks($page_id);

            dbglog($backlinks, "backlinks: all backlinks to: $page_id");

            $renderer->doc .= '<div id="plugin__backlinks">'.PHP_EOL;

            $filterNS = $data[1];
            if (!empty($backlinks) && !empty($filterNS)) {
                if (stripos($filterNS, "!", 0) === 0) {
                    $filterNS = substr($filterNS, 1);
                    dbglog($filterNS, "backlinks: exluding all of namespace: $filterNS");
                    $backlinks = array_filter($backlinks, function($ns) use($filterNS) {
                        return stripos($ns, $filterNS, 0) !== 0;
                    });
                } else {
                    dbglog($filterNS, "backlinks: including namespace: $filterNS only");
                    $backlinks = array_filter($backlinks, function($ns) use($filterNS) {
                        return stripos($ns, $filterNS, 0) === 0;
                    });
                }
            }

            dbglog($backlinks, "backlinks: all backlinks to be rendered");

            if (!empty($backlinks)) {

                $renderer->doc .= '<ul class="idx">';

                foreach ($backlinks as $backlink) {
                    $name = p_get_metadata($backlink, 'title');
                    if (empty($name)) {
                        $name = $backlink;
                    }
                    $renderer->doc .= '<li><div class="li">';
                    $renderer->doc .= html_wikilink(':'.$backlink, $name);
                    $renderer->doc .= '</div></li>'.PHP_EOL;
                }

                $renderer->doc .= '</ul>'.PHP_EOL;
            } else {
                $renderer->doc .= "<strong>Plugin Backlinks: ".$lang['nothingfound']."</strong>".PHP_EOL;
            }

            $renderer->doc .= '</div>'.PHP_EOL;

            return true;
        }
        return false;
    }
}
// vim:ts=4:sw=4:et:
