<?php

namespace Kiki;

use Kiki\Core;
use Kiki\Storage;

class Picture
{
    static function delete( $id )
    {
        $db = Core::getDb();
        $user = Core::getUser();

        // Check if user is allowed to delete picture
        // TODO: add user_id to pictures or storage, to avoid having to query album
        $qAlbums = $db->buildQuery( "SELECT a.id, o.user_id FROM albums a LEFT JOIN objects o ON o.object_id=a.object_id LEFT JOIN album_pictures ap ON ap.album_id=a.id WHERE picture_id=%d", $id );
        $albums = $db->getObjects( $qAlbums );
        foreach( $albums as $album )
        {
            if ( $album->user_id != $user->id() )
                return false;
        }

        // Delete picture from album(s)
        $qAlbumPictures = $db->buildQuery( "DELETE FROM album_pictures WHERE picture_id=%d", $id );
        $db->query($qAlbumPictures);

        // Delete storage item
        $qStorageId = $db->buildQuery( "SELECT storage_id FROM pictures WHERE id=%d", $id );
        $storageId = $db->getSingleValue( $qStorageId );
        Storage::delete( $storageId );

        // Delete picture itself
        $qPicture = $db->buildQuery( "DELETE from pictures WHERE id=%d", $id );
        $db->query($qPicture);

        return true;
    }
}
