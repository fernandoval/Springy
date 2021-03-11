<?php
/**	\file
 *  Springy.
 *
 *  \brief		Classe para geração de arquivos ZIP.
 *  \copyright	Copyright (c) 2007-2016 Fernando Val
 *  \author		(c) 2003 by Pascal Rehfeldt - Pascal@Pascal-Rehfeldt.com, under license GNU General Public License (Version 2, June 1991)
 *  \author		Fernando Val - fernando.val@gmail.com
 *  \warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version	0.3.0.11
 *  \ingroup	framework
 */

namespace Springy\Utils;

/**
 *  \brief Classe para geração de arquivos ZIP.
 *
 *  Esta classe foi baseada no excelente trabalho de Pascal Rehfeldt.\n
 *  Conversão para PHP 5, melhorias, documentação e adaptação por Fernando Val.
 *
 *  \copyright (c) 2003 by Pascal Rehfeldt with changes by Fernando Val
 *  \copyright Under license GNU General Public License (Version 2, June 1991)
 *  \author Pascal@Pascal-Rehfeldt.com
 *  \author Fernando Val - fernando.val@gmail.com
 *
 *  You can use ZIPlib to add different resources to a ZIP file.
 */
class ZipFile
{
    /// Nome do arquivo de saída
    private $output_filename = 'archive.zip';
    private $datasec = [];
    private $ctrl_dir = [];
    private $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";
    private $old_offset = 0;
    private $pathToFPDF = null;
    private $root_path = '';

    /**
     *	\brief Construtor da classe.
     */
    public function __construct($output_filename = 'archive.zip', $root_path = '')
    {
        $this->output_filename = $output_filename;
        $this->root_path = str_replace('\\', '/', $root_path);
        //$this->pathToFPDF      = $FPDF;
    }

    /**
     *	\brief Troca a estensão de um arquivo.
     */
    private function replaceSuffix($file, $suffix = 'pdf')
    {
        $arr = explode('.', $file);
        unset($arr[count($arr) - 1]);
        $file = null;
        foreach ($arr as $v) {
            $file .= $v . '.';
        }
        $file .= $suffix;

        return $file;
    }

    /**
     *	\brief Converte uma data/hora no formato UNIX para o formato DOS.
     */
    private function unix2DosTime($unixtime = 0)
    {
        $timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);

        if ($timearray['year'] < 1980) {
            $timearray['year'] = 1980;
            $timearray['mon'] = 1;
            $timearray['mday'] = 1;
            $timearray['hours'] = 0;
            $timearray['minutes'] = 0;
            $timearray['seconds'] = 0;
        }

        return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) |
            ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
    }

    /**
     *	\brief Pega o conteúdo de um diretório.
     */
    private function getDirContent($dirName = './')
    {
        if (is_dir($dirName)) {
            $dir = new DeepDir();
            $dir->setDir($dirName);
            $dir->load();

            return $dir->getFiles();
            /*
            O código comentado abaixo foi um experimento utilizando RecursiveIteratorIterator que
            demonostrou ser muito mais lento que uma classe escrita em PHP.
            Código mantido para referência.
            */
            // $dir = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dirName), \RecursiveIteratorIterator::LEAVES_ONLY);
            // $arr = array();
            // foreach($dir as $name => $object){
                // $arr[] = $name;
            // }
            // return $arr;
        }
    }

    /**
     *	\brief Lê o conteúdo de um arquivo.
     */
    private function readFile($file)
    {
        if (is_file($file)) {
            if ($fp = fopen($file, 'rb')) {
                $content = fread($fp, filesize($file));
                fclose($fp);

                return $content;
            }
        }

        return '';
    }

    /**
     *	\brief Monta o conteúdo do ZIP.
     */
    private function zipContent()
    {
        $data = implode(null, $this->datasec);
        $ctrldir = implode(null, $this->ctrl_dir);

        return $data .
               $ctrldir .
               $this->eof_ctrl_dir .
               pack('v', count($this->ctrl_dir)) . // total # of entries "on this disk"
               pack('v', count($this->ctrl_dir)) . // total # of entries overall
               pack('V', strlen($ctrldir)) . // size of central dir
               pack('V', strlen($data)) . // offset to start of central dir
               "\x00\x00";                             // .zip file comment length
    }

    /**
     *	\brief Adiciona o conteúdo de um diretório.
     *
     *	void addDirContent( \c resource dir_handle )
     *
     *	Para adicionar um diretório completo ao ZIP você pode usar esta função.
     *	Não importa se há arquivos texto ou binários no diretório.
     *	Esta função faz uso da classe DeepDir já adicionada a este framework.
     */
    public function addDirContent($dir = './')
    {
        foreach ($this->getDirContent($dir) as $input) {
            $this->addFile(str_replace('.//', null, $input));
        }
    }

    /**
     *	\brief Adiciona um conteúdo como arquivo.
     *
     *	void addContentAsFile( \c string Content, \c string Filename [, \c int Time] )
     *
     *	Use addContentAsFile() para adicionar o conteúdo de um arquivo para seu ZIP.
     *	O conteúdo do arquivo precisa estar em um \c string.
     *
     *	Esta funcão é muito útil para adicionar conteúdo oriundo de um campo BLOB de uma
     *	base de dados.
     *
     *	Para adicionar um arquivo completo, você deve usar a função addFile().
     */
    public function addContentAsFile($data, $name, $time = 0)
    {
        if (mb_check_encoding($name, 'UTF-8')) {
            $name = Strings_UTF8::convertToWindowsCP1252($name);
        } else {
            $name = Strings_ANSI::convertToWindowsCP1252($name);
        }

        $name = str_replace('\\', '/', $name);
        $name = preg_replace('/^(' . str_replace('/', '\/', $this->root_path) . ')?(.*)$/', '\\2', $name);

        $dtime = dechex($this->unix2DosTime($time));
        $hexdtime = '\x' . $dtime[6] . $dtime[7]
                  . '\x' . $dtime[4] . $dtime[5]
                  . '\x' . $dtime[2] . $dtime[3]
                  . '\x' . $dtime[0] . $dtime[1];

        eval('$hexdtime = "' . $hexdtime . '";');

        $fr = "\x50\x4b\x03\x04";
        $fr .= "\x14\x00";            // ver needed to extract
        $fr .= "\x00\x00";            // gen purpose bit flag
        $fr .= "\x08\x00";            // compression method
        $fr .= $hexdtime;             // last mod time and date

        // "local file header" segment
        $unc_len = strlen($data);
        $crc = crc32($data);
        $zdata = gzcompress($data);
        $zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
        $c_len = strlen($zdata);
        $fr .= pack('V', $crc);             // crc32
        $fr .= pack('V', $c_len);           // compressed filesize
        $fr .= pack('V', $unc_len);         // uncompressed filesize
        $fr .= pack('v', strlen($name));    // length of filename
        $fr .= pack('v', 0);                // extra field length
        $fr .= $name;

        // "file data" segment
        $fr .= $zdata;

        // "data descriptor" segment (optional but necessary if archive is not
        // served as file)
        $fr .= pack('V', $crc);                 // crc32
        $fr .= pack('V', $c_len);               // compressed filesize
        $fr .= pack('V', $unc_len);             // uncompressed filesize

        // add this entry to array
        $this->datasec[] = $fr;
        $new_offset = strlen(implode('', $this->datasec));

        // now add to central directory record
        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .= "\x00\x00";                // version made by
        $cdrec .= "\x14\x00";                // version needed to extract
        $cdrec .= "\x00\x00";                // gen purpose bit flag
        $cdrec .= "\x08\x00";                // compression method
        $cdrec .= $hexdtime;                 // last mod time & date
        $cdrec .= pack('V', $crc);           // crc32
        $cdrec .= pack('V', $c_len);         // compressed filesize
        $cdrec .= pack('V', $unc_len);       // uncompressed filesize
        $cdrec .= pack('v', strlen($name)); // length of filename
        $cdrec .= pack('v', 0);             // extra field length
        $cdrec .= pack('v', 0);             // file comment length
        $cdrec .= pack('v', 0);             // disk number start
        $cdrec .= pack('v', 0);             // internal file attributes
        $cdrec .= pack('V', 32);            // external file attributes - 'archive' bit set

        $cdrec .= pack('V', $this->old_offset); // relative offset of local header
        $this->old_offset = $new_offset;

        $cdrec .= $name;

        // optional extra field, file comment goes here
        // save to central directory
        $this->ctrl_dir[] = $cdrec;
    }

    /**
     *	\brief Adiciona o conteúdo de um arquivo.
     *
     *	void addFile( \c resource Filename )
     *
     *	addFile() pega um arquivo, lê seu conteúdo e o adiciona ao seu ZIP.
     *	Esta função pode ler arquivos texto e binários.
     */
    public function addFile($file)
    {
        if (is_file($file)) {
            $this->addContentAsFile($this->readFile($file), $file);
        }
    }

    /**
     *	\brief Adiciona um arquivo convertendo seu conteúdo para PDF.
     *
     *	\note Esta função foi omitira propositadamente até que a classe FPDF que é utilizada por ela
     *	seja adicionada ao framework
     *
     *	void addFileAsPDF( \c resource file_handle[, \c string title[, \c string autor]] )
     *
     *	Esta função adiciona um arquivo texto (ASCII) como um PDF ao ZIP.
     *
     *	\note Arquivos binários não são suportados por esta função.
     */
    public function addFileAsPDF($file, $title = 'PDF File', $author = 'Anonymous')
    {
        return;

        //You need FPDF to use this function!
        //get it at http://www.fpdf.org/

        if (include $this->pathToFPDF) {
            $pdf = new PDF();
            $pdf->Open();

            //edit this as you need it

            $pdf->SetTitle($title);
            $pdf->SetAuthor($author);
            $pdf->PrintChapter(1, $author, $file);

            //nothing to edit below!

            $this->addContentAsFile($pdf->getBuffer(), $this->replaceSuffix($file));
        } else {
            $filecontent = implode(null, file($file));

            $content .= '********************************************' . "\n";
            $content .= '*                                          *' . "\n";
            $content .= '*   Couldn\'t find FPDF!                   *' . "\n";
            $content .= '*   Adding this File as plain text file.   *' . "\n";
            $content .= '*                                          *' . "\n";
            $content .= '*   Below this box is the sourcefile.      *' . "\n";
            $content .= '*                                          *' . "\n";
            $content .= '********************************************' . "\n";

            $content .= ' ' . "\n";
            $content .= ' ' . "\n";
            $content .= ' ' . "\n";

            $content .= $filecontent;

            $this->addContentAsFile($content, $file);
        }
    }

    /**
     *	\brief Salva o arquivo ZIP.
     */
    public function save($path = '')
    {
        $return = false;

        if (substr($path, -1) != DIRECTORY_SEPARATOR) {
            $path .= DIRECTORY_SEPARATOR;
        }

        if ($fileh = fopen($path . $this->output_filename, 'wb+')) {
            if (fwrite($fileh, $this->zipContent())) {
                $return = true;
            }
            fclose($fileh);
        }

        return $return;
    }

    /**
     *	\brief Envia o arquivo para o browser do usuário sem salvá-lo no disco.
     */
    public function download()
    {
        header('Content-Type: application/x-zip');
        header('Content-Disposition: inline; filename="' . $this->output_filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        echo $this->zipContent();
    }
}
