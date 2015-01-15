<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
###############################################
#### shared mem functions
/*
  for debugging these
  use `ipcs` to view current memory
  use `ipcrm -m {shmid}` to remove
  on some systems use `ipcclean` to clean up unused memory if you
  don't want to do it by hand
 */
###############################################

namespace Glial\Cli;

class Shmop
{

    private $TMPDIR = NULL;
    private $TMPPRE = NULL;
    private $MEMCOMPRESS = FALSE;
    private $MEMCOMPRESSLVL = 0;
    private $memUsed = 0;
    private $Index = 'index';

    public function __construct(
    $TMPDIR = "/var/", $TMPPRE = "lock/", $MEMCOMPRESS = FALSE, $MEMCOMPRESSLVL = 0)
    {

        $this->TMPDIR = $TMPDIR;
        $this->TMPPRE = $TMPPRE;
        $this->MEMCOMPRESS = $MEMCOMPRESS;
        $this->MEMCOMPRESSLVL = $MEMCOMPRESSLVL;
        
        
        $shmkey = $this->get_key(5000, $this->Index);
        
        $arr = [];
        
        json_encode($arr);
        
        $shm_size = $this->writemem($shmkey,"50");
        
        
    }

    public function get_key($fsize, $file)
    {

        $filename = $this->TMPDIR . $this->TMPPRE . $file;

        if (!file_exists($filename)) {
            touch($filename);
        }

        $shmkey = shmop_open(ftok($filename, 'R'), "c", 0777, $fsize);

        if (!$shmkey) {
            return FALSE;
        } else {
            return $shmkey;
        }
    }

    public function writemem($shmkey, $fdata)
    {
        if ($this->MEMCOMPRESS && function_exists('gzcompress')) {
            $fdata = gzcompress($fdata, $this->MEMCOMPRESSLVL);
        }
        $fsize = strlen($fdata);
        $shm_bytes_written = shmop_write($shmkey, $fdata, 0);
        $this->updatestats($shm_bytes_written, "add");
        if ($shm_bytes_written != $fsize) {
            return false;
        } else {
            return $shm_bytes_written;
        }
    }

    public function readmem($shmkey, $shm_size)
    {
        $my_string = shmop_read($shmkey, 0, $shm_size);
        if ($this->MEMCOMPRESS && function_exists('gzuncompress')) {
            $my_string = gzuncompress($my_string);
        }
        if (!$my_string) {
            return false;
        } else {
            return $my_string;
        }
    }

    public function deletemem($shmkey)
    {
        $size = shmop_size($shmkey);
        if ($size > 0) {
            $this->updatestats($size, "del");
        }
        if (!shmop_delete($shmkey)) {
            shmop_close($shmkey);
            return false;
        } else {
            shmop_close($shmkey);
            return true;
        }
    }

    public function closemem($shmkey)
    {
        if (!shmop_close($shmkey)) {
            return false;
        } else {
            return true;
        }
    }

    public function iskey($size, $key)
    {
        if ($ret = $this->get_key($size, $key)) {
            return $ret;
        } else {
            return false;
        }
    }

    private function updatestats($size, $type)
    {
        if ($type == "add") {
            $this->memUsed += $size;
        } else if ($type == "del") {
            $this->memUsed -= $size;
        }
        return $this->memUsed;
    }

    public function amountMemUsed()
    {
        return $this->memUsed;
    }

    public function malloc($file, $fsize)
    {
        
        
        
        
        $this->get_key($fsize, $file);
        
        $size = strlen($file);
        $this->Index;
        
    }

}
