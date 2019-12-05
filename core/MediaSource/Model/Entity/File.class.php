<?php declare(strict_types=1);

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2015
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Cloudrexx" is a registered trademark of Cloudrexx AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * Base class for files (excluding folders)
 *
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_mediasource
 */

namespace Cx\Core\MediaSource\Model\Entity;

/**
 * Exception on invalid action on a file
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_mediasource
 */
class FileException extends \Exception {};

/**
 * Base class for files (excluding folders)
 *
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_mediasource
 */
abstract class File extends \Cx\Model\Base\EntityBase {

    /**
     * The file's path relative to the FS' root with a leading directory separator
     * @var string
     */
    protected $file;

    /**
     * The file system instance this file belongs to
     *
     * @var \Cx\Core\MediaSource\Model\Entity\LocalFileSystem
     */
    protected $fileSystem;

    /**
     * Creates a new instance of this class
     *
     * @param string $file
     * @param FileSystem $fileSystem
     */
    public function __construct(string $file, FileSystem $fileSystem) {
        if (strpos($file, '/') === 0) {
            $this->file = $file;
        } else {
            \DBG::msg(__METHOD__.": $file without leading slash supplied!");
            $this->file = '/' . $file;
        }
        $this->file = $file;
        $this->fileSystem = $fileSystem;
    }

    /**
     * Returns the FileSystem for this file
     *
     * @return FileSystem This file's FileSystem
     */
    public function getFileSystem(): FileSystem {
        return $this->fileSystem;
    }

    /**
     * Returns the path (without filename) for this file
     *
     * @return string Path without filename
     */
    public function getPath(): string {
        return pathinfo($this->file, PATHINFO_DIRNAME);
    }

    /**
     * Returns the filename (without path and extension) for this file
     *
     * @return string Filename without path and extension
     */
    public function getName(): string {
        return pathinfo($this->file, PATHINFO_FILENAME);
    }

    /**
     * Returns the filename (without path including extension) for this file
     *
     * @return string Filename without path including extension
     */
    public function getFullName(): string {
        return pathinfo($this->file, PATHINFO_BASENAME);
    }

    /**
     * Returns the filename relative to the FS' root
     *
     * @return string File path including filename and extension
     */
    public function getFullPath(): string {
        return $this->file;
    }

    /**
     * Returns this file's extension
     *
     * @return string File extension
     */
    public function getExtension(): string {
        return pathinfo($this->file, PATHINFO_EXTENSION);
    }

    /**
     * Returns the MIME type of this file
     *
     * @return string MIME type
     */
    public function getMimeType(): string {
        return \Mime::getMimeTypeForExtension($this->getExtension());
    }

    /**
     * Returns the full file path (path and filename including extension)
     */
    public function __toString(): string {
        return $this->getFullPath();
    }
}
