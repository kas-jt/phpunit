<?php
/**
 * PHPUnit
 *
 * Copyright (c) 2002-2007, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Testing
 * @package    PHPUnit
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2002-2007 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://www.phpunit.de/
 * @since      File available since Release 3.2.0
 */

require_once 'PHPUnit/Util/Filter.php';
require_once 'PHPUnit/Util/Filesystem.php';
require_once 'PHPUnit/Util/Template.php';
require_once 'PHPUnit/Util/Report/Node.php';
require_once 'PHPUnit/Util/Report/Node/File.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

/**
 * Represents a directory in the code coverage information tree.
 *
 * @category   Testing
 * @package    PHPUnit
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2002-2007 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 3.2.0
 */
class PHPUnit_Util_Report_Node_Directory extends PHPUnit_Util_Report_Node
{
    const LOW_UPPER_BOUND  = 35;
    const HIGH_LOWER_BOUND = 70;

    /**
     * @var    PHPUnit_Util_Report_Node[]
     * @access protected
     */
    protected $children = array();

    /**
     * @var    PHPUnit_Util_Report_Node_Directory[]
     * @access protected
     */
    protected $directories = array();

    /**
     * @var    PHPUnit_Util_Report_Node_File[]
     * @access protected
     */
    protected $files = array();

    /**
     * @var    integer
     * @access protected
     */
    protected $numExecutableLines = -1;

    /**
     * @var    integer
     * @access protected
     */
    protected $numExecutedLines = -1;

    /**
     * Adds a new directory.
     *
     * @return PHPUnit_Util_Report_Node_Directory
     * @access public
     */
    public function addDirectory($name)
    {
        $directory = new PHPUnit_Util_Report_Node_Directory(
          $name,
          $this,
          $this->highlight
        );

        $this->children[]    = $directory;
        $this->directories[] = &$this->children[count($this->children) - 1];

        return $directory;
    }

    /**
     * Adds a new file.
     *
     * @param  string $name
     * @param  array  $lines
     * @return PHPUnit_Util_Report_Node_File
     * @throws RuntimeException
     * @access public
     */
    public function addFile($name, array $lines)
    {
        $file = new PHPUnit_Util_Report_Node_File(
          $name,
          $this,
          $this->highlight,
          $lines
        );

        $this->children[] = $file;
        $this->files[]    = &$this->children[count($this->children) - 1];

        $this->numExecutableLines = -1;
        $this->numExecutedLines   = -1;

        return $file;
    }

    /**
     * Returns the directories in this directory.
     *
     * @return
     * @access public
     */
    public function getDirectories()
    {
        return $this->directories;
    }

    /**
     * Returns the files in this directory.
     *
     * @return
     * @access public
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Returns the number of executable lines.
     *
     * @return integer
     * @access public
     */
    public function getNumExecutableLines()
    {
        if ($this->numExecutableLines == -1) {
            $this->numExecutableLines = 0;

            foreach ($this->children as $child) {
                $this->numExecutableLines += $child->getNumExecutableLines();
            }
        }

        return $this->numExecutableLines;
    }

    /**
     * Returns the number of executed lines.
     *
     * @return integer
     * @access public
     */
    public function getNumExecutedLines()
    {
        if ($this->numExecutedLines == -1) {
            $this->numExecutedLines = 0;

            foreach ($this->children as $child) {
                $this->numExecutedLines += $child->getNumExecutedLines();
            }
        }

        return $this->numExecutedLines;
    }

    /**
     * Renders this node.
     *
     * @param string $target
     * @param string $title
     * @param string $charset
     * @access public
     */
    public function render($target, $title, $charset = 'ISO-8859-1')
    {
        $this->doRender($target, $title, $charset);

        foreach ($this->children as $child) {
            $child->render($target, $title, $charset);
        }
    }

    /**
     * @param  string   $target
     * @param  string   $title
     * @param  string   $charset
     * @access protected
     */
    protected function doRender($target, $title, $charset)
    {
        $cleanId = PHPUnit_Util_Filesystem::getSafeFilename($this->getId());
        $file    = $target . $cleanId . '.html';

        $template = new PHPUnit_Util_Template(
          PHPUnit_Util_Report::$templatePath . 'coverage_directory.html'
        );

        $this->setTemplateVars($template, $title, $charset);

        $template->setVar(
          array(
            'items',
            'low_upper_bound',
            'high_lower_bound'
          ),
          array(
            $this->renderItems(),
            self::LOW_UPPER_BOUND,
            self::HIGH_LOWER_BOUND
          )
        );

        $template->renderTo($file);
    }

    /**
     * @return string
     * @access protected
     */
    protected function renderItems()
    {
        $items  = $this->doRenderItems($this->directories);
        $items .= $this->doRenderItems($this->files);

        return $items;
    }

    /**
     * @param  array    $items
     * @return string
     * @access protected
     */
    protected function doRenderItems(array $items)
    {
        $result = '';

        foreach ($items as $item) {
            $itemTemplate = new PHPUnit_Util_Template(
              PHPUnit_Util_Report::$templatePath . 'coverage_item.html'
            );

            $floorPercent = floor($item->getExecutedPercent());

            if ($floorPercent < self::LOW_UPPER_BOUND) {
                $color = 'scarlet_red';
                $level = 'Lo';
            }

            else if ($floorPercent >= self::LOW_UPPER_BOUND &&
                     $floorPercent <  self::HIGH_LOWER_BOUND) {
                $color = 'butter';
                $level = 'Med';
            }

            else {
                $color = 'chameleon';
                $level = 'Hi';
            }

            $itemTemplate->setVar(
              array(
                'link',
                'color',
                'level',
                'executed_width',
                'executed_percent',
                'not_executed_width',
                'executable_lines',
                'executed_lines'
              ),
              array(
                $item->getLink(FALSE, FALSE),
                $color,
                $level,
                floor($item->getExecutedPercent()),
                $item->getExecutedPercent(),
                100 - floor($item->getExecutedPercent()),
                $item->getNumExecutableLines(),
                $item->getNumExecutedLines()
              )
            );

            $result .= $itemTemplate->render();
        }

        return $result;
    }
}
?>
