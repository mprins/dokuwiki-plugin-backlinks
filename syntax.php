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

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class.
 */
class syntax_plugin_backlinks extends DokuWiki_Syntax_Plugin {
    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php.
     *
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    public function getType() {
        return 'substition';
    }

    /**
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    public function getPType() {
        return 'block';
    }

    /**
     * @see Doku_Parser_Mode::getSort()
     */
    public function getSort() {
        return 304;
    }

    /**
     * Connect pattern to lexer.
     *
     * @see Doku_Parser_Mode::connectTo()
     */
    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{backlinks>.+?\}\}', $mode, 'plugin_backlinks');
    }

    /**
     * Handler to prepare matched data for the rendering process.
     *
     * @see DokuWiki_Syntax_Plugin::handle()
     */
    public function handle($match, $state, $pos, Doku_Handler $handler) {
        // strip {{backlinks> from start and }} from end
        $match = substr($match, 12, -2);

        $includeNS = '';
        if(strstr($match, "#")) {
            $includeNS = substr(strstr($match, "#", false), 1);
            $match     = strstr($match, "#", true);
        }

        return (array($match, $includeNS));
    }

    /**
     * Handles the actual output creation.
     *
     * @see DokuWiki_Syntax_Plugin::render()
     */
    public function render($mode, Doku_Renderer $renderer, $data) {
        global $lang;
        global $INFO;
        global $ID;

        $id = $ID;
        // If it's a sidebar, get the original id.
        if($INFO != null) {
            $id = $INFO['id'];
        }
        $match = $data[0];
        $match = ($match == '.') ? $id : $match;
        if(strstr($match, ".:")) {
            resolve_pageid(getNS($id), $match, $exists);
        }

        if($mode == 'xhtml') {
            $renderer->info['cache'] = false;

            $backlinks = ft_backlinks($match);

            dbglog($backlinks, "backlinks: all backlinks to: $match");

            $renderer->doc .= '<div id="plugin__backlinks">' . "\n";

            $filterNS = $data[1];
            if(!empty($backlinks) && !empty($filterNS)) {
                if(stripos($filterNS, "!", 0) === 0) {
                    $filterNS = substr($filterNS, 1);
                    dbglog($filterNS, "backlinks: exluding all of namespace: $filterNS");
                    $backlinks = array_filter(
                        $backlinks, function ($ns) use ($filterNS) {
                        return stripos($ns, $filterNS, 0) !== 0;
                    }
                    );
                } else {
                    dbglog($filterNS, "backlinks: including namespace: $filterNS only");
                    $backlinks = array_filter(
                        $backlinks, function ($ns) use ($filterNS) {
                        return stripos($ns, $filterNS, 0) === 0;
                    }
                    );
                }
            }

            dbglog($backlinks, "backlinks: all backlinks to be rendered");

            if(!empty($backlinks)) {

                $renderer->doc .= '<ul class="idx">';
$i=0; $more=false;
                foreach($backlinks as $backlink) {
                    if(++$i > $this->getConf('number')) {$more=true;break;}
                    $name = p_get_metadata($backlink, 'title');
                    if(empty($name)) {
                        $name = $backlink;
                    }
                    $renderer->doc .= '<li><div class="li">';
                    $renderer->doc .= html_wikilink(':' . $backlink, $name);
                    $renderer->doc .= '</div></li>' . "\n";
                }

                $renderer->doc .= '</ul>' . "\n";
                                if ($more) {
                    $renderer->doc .= tpl_actionlink('backlink', '','',count($backlinks)-$i.$this->getLang('more'), true);
                }
                
            } else {
                $renderer->doc .= "<strong>Plugin Backlinks: " . $lang['nothingfound'] . "</strong>" . "\n";
            }

            $renderer->doc .= '</div>' . "\n";

            return true;
        }
        return false;
    }
}
