<?php
/**
 * User: SaWey
 * Date: 12/12/13
 */

namespace phpList;


class Attachment
{
    public $id = 0;
    public $filename;
    public $remotefile;
    public $mimetype;
    public $description;
    public $size;

    public function __construct($filename, $remotefile, $mimetype, $description, $size)
    {
        $this->filename = $filename;
        $this->remotefile = $remotefile;
        $this->mimetype = $mimetype;
        $this->description = $description;
        $this->size = $size;
    }

    public function save()
    {
        if ($this->id != 0) {
            $this->update();
        } else {
            phpList::DB()->query(
                sprintf(
                    'INSERT INTO %s (filename,remotefile,mimetype,description,size)
                    VALUES("%s","%s","%s","%s",%d)',
                    Config::getTableName('attachment'),
                    $this->filename,
                    $this->remotefile,
                    $this->mimetype,
                    $this->description,
                    $this->size
                )
            );
            $this->id = phpList::DB()->insertedId();
        }
    }

    public function update()
    {
        throw new \Exception('Not implemented yet');
    }

    public static function getAttachment($id)
    {
        $result = phpList::DB()->fetchAssocQuery(
            sprintf(
                'SELECT * FROM %s
                WHERE a.id = %d',
                Config::getTableName('attachment'),
                $id
            )
        );
        return (object)$result;
    }

    public function removeIfOrphaned()
    {
        $result = phpList::DB()->fetchAssocQuery(
            sprintf(
                'SELECT messageid FROM %s
                WHERE attachmentid = %d',
                Config::getTableName('message_attachment'),
                $this->id
            )
        );
        if (empty($result)) {
            //remove from disk
            @unlink($this->remotefile);
        }
    }

} 