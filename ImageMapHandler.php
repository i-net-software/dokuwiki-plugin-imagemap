<?php

namespace dokuwiki\plugin\imagemapping;

use dokuwiki\Parsing\Handler\CallWriterInterface;

/**
 * Custom CallWriter for the imagemapping plugin
 */
class ImageMapHandler implements CallWriterInterface
{
    /** @var CallWriterInterface the parent call writer */
    public $CallWriter;

    /** @var array the local call stack */
    protected $calls = [];

    /** @var string Name of this imagemap FIXME currenlty unsused?*/
    private $mapname;

    /**
     * Constructor
     *
     * @param CallWriterInterface $CallWriter the "parent" call writer
     */
    public function __construct($mapname, CallWriterInterface $CallWriter)
    {
        $this->mapname = $mapname;
        $this->CallWriter = $CallWriter;
    }

    /** @inheritdoc */
    public function writeCall($call)
    {
        $this->calls[] = $call;
    }

    /** @inheritdoc */
    public function writeCalls($calls)
    {
        $this->calls = array_merge($this->calls, $calls);
    }

    /** @inheritdoc */
    public function finalise()
    {
        $last_call = end($this->calls);
        $this->process();
        $this->addPluginCall([DOKU_LEXER_EXIT], $last_call[2]);
        $this->CallWriter->finalise(); // FIXME do we really need to call it?
    }

    /**
     * Get the parent call writer
     *
     * @return CallWriterInterface
     */
    public function getCallWriter()
    {
        return $this->CallWriter;
    }

    /**
     * Process the local call stack
     *
     * @return void
     */
    public function process()
    {
        $last_call = end($this->calls);
        $first_call = array_shift($this->calls);

        $this->CallWriter->writeCall($first_call);
        $this->processLinks($first_call[2]);

        if (!empty($this->calls)) {
            $this->addPluginCall([DOKU_LEXER_MATCHED, 'divstart'], $first_call[2]);
            //Force a new paragraph
            $this->CallWriter->writeCall(['eol', [], $this->calls[0][2]]);
            $this->CallWriter->writeCalls($this->calls);
            $this->addPluginCall([DOKU_LEXER_MATCHED, 'divend'], $last_call[2]);
        }
    }

    /**
     * Adds a call to the imagemap plugin with the given data
     *
     * The syntax component will be called with the given data and handle the various sub modes
     *
     * @param array $args [DOKU_LEXER_*, 'submode', params...]
     * @param int $pos
     * @return void
     */
    protected function addPluginCall($args, $pos)
    {
        $this->CallWriter->writeCall(['plugin', ['imagemapping', $args, $args[0]], $pos]);
    }

    /**
     * Add a new area call to the call stack
     *
     * @param int $pos The position in the source
     * @param string $type The type of the link (internallink, externallink, ...)
     * @param string $title The link title including the coordinates
     * @param string $url The link part of the link
     * @param string $wiki The interwiki identifier for interwiki links
     * @return string The title without the coordinates
     */
    protected function addArea($pos, $type, $title, $url, $wiki = null)
    {
        if (preg_match('/^(.*)@([^@]+)$/u', $title, $match)) {
            $coords = explode(',', $match[2]);
            if (count($coords) == 3) {
                $shape = 'circle';
            } elseif (count($coords) == 4) {
                $shape = 'rect';
            } elseif (count($coords) >= 6) {
                $shape = 'poly';
            } else {
                return $title;
            }
            $coords = array_map('trim', $coords);
            $title = trim($match[1]);

            $coords = join(',', $coords);
            $coords = trim($coords);

            $this->addPluginCall(
                [DOKU_LEXER_MATCHED, 'area', $shape, $coords, $type, $title, $url, $wiki],
                $pos
            );
        }
        return $title;
    }

    /**
     * Walk through the call stack and process all links
     *
     * This will add the imagemap areas to the call stack and remove the coordinates from the link titles
     *
     * @todo simplify more and add tests
     * @param int $pos The source position
     * @return void
     */
    protected function processLinks($pos)
    {
        for ($n = 0; $n < count($this->calls); $n++) {
            $data =& $this->calls[$n][1];
            $type = $this->calls[$n][0];
            switch ($type) {
                case 'plugin':
                    // support for other plugins that use the imagemap syntax (e.g. the popupviewer plugin)
                    $plugin = plugin_load('syntax', $data[0]);
                    if ($plugin != null && method_exists($plugin, 'convertToImageMapArea')) {
                        $plugin->convertToImageMapArea($this, $data[1], $pos);
                    }
                    break;
                case 'internallink':
                case 'locallink':
                case 'externallink':
                case 'emaillink':
                case 'windowssharelink':
                    if (is_array($data[1])) {
                        $title = $data[1]['title'] ?? '';
                    } else {
                        $title = $data[1];
                    }
                    $title = $this->addArea($pos, $type, $title, $data[0]);
                    if (is_array($data[1])) {
                        $data[1]['title'] = $title;
                    } else {
                        $data[1] = $title;
                    }
                    break;
                case 'interwikilink':
                    if (is_array($data[1])) {
                        $title = $data[1]['title'];
                    } else {
                        $title = $data[1];
                    }
                    $title = $this->addArea($pos, $type, $title, $data[3], $data[2]);
                    if (is_array($data[1])) {
                        $data[1]['title'] = $title;
                    } else {
                        $data[1] = $title;
                    }
                    break;
            }
        }
    }

}
