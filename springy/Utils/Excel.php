<?php
/**	\file
 *	Springy.
 *
 *	\brief      Classe para construção de arquivos no formato Microsoft(R) Excel(R).
 *  \copyright  (c) 2007-2016 Fernando Val
 *  \author     Fernando Val - fernando.val@gmail.com
 *	\note       Classe baseada no trabalho de Harish Chauhan de 31/12/2004
 *	\version    0.4.6
 *	\ingroup    framework
 */

namespace Springy\Utils;

/**
 *  \brief Classe para construção de arquivos no formato Microsoft(R) Excel(R).
 */
class Excel
{
    // Handle do arquivo aberto
    private $fp = null;

    // Flags internos
    private $error = 0;
    private $newRow = false;
    private $state = null;

    private $nameFile = null;

    private $columns = [];

    // Constantes de erro
    const ERR_ANOTHER_FILE_OPENED = 1001;
    const ERR_INVALID_FILE_NAME = 1002;
    const ERR_UNABLE_OPEN_CREATE_FILE = 1003;
    const ERR_NO_FILE_OPENED = 1004;
    const ERR_INVALID_ARGUMENT_ARRAY = 2001;

    /**
     *	\brief Método construtor da classe.
     *
     *	@Params : $file  : file name of excel file to be created.
     *	@Return : On Success Valid File Pointer to file
     *             On Failure return false
     */
    public function __construct($file = '', $bsc = 'CELLPAR')
    {
        return $this->open($file);
    }

    /**
     *	\brief Método destrutor da classe.
     *
     *	Caso haja algum arquivo aberto, irá fechá-lo primeiro.
     */
    public function __destruct()
    {
        if (!is_null($this->fp)) {
            $this->close();
        }
    }

    /**
     *	\brief Retorna o cC3digo do último erro.
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     *	\brief Retorna uma mensagem texto do último erro.
     */
    public function getErrorMessage()
    {
        $message = '';
        switch ($this->error) {
            case self::ERR_ANOTHER_FILE_OPENED:
                $message = 'Error : Another file is opend .Close it to save the file.';
                break;
            case self::ERR_INVALID_FILE_NAME:
                $message = 'Error : Invalid or missing file name.';
                break;
            case self::ERR_UNABLE_OPEN_CREATE_FILE:
                $message = 'Error: Unable to open/create File. You may not have permmsion to write the file.';
                break;
            case self::ERR_NO_FILE_OPENED:
                $message = 'Error : Please open the file.';
                break;
            case self::ERR_INVALID_ARGUMENT_ARRAY:
                $message = 'Error : Argument is not valid. Supply an valid Array.';
                break;
        }

        return $message;
    }

    /**
     *	\brief Define o nome do arquivo.
     */
    public function setNameFile($nameFile)
    {
        $this->nameFile = $nameFile;
    }

    /**
     *	\brief Abre o arquivo para gravação.
     *
     *	@Params : $file  : file name of excel file to be created.
     *                if you are using file name with directory i.e. test/myFile.xls
     *                then the directory must be existed on the system and have permissioned properly
     *                to write the file.
     *	@Return : On Success Valid File Pointer to file
     *                On Failure return false
     */
    public function open($file)
    {
        if (!is_null($this->fp)) {
            $this->error = self::ERR_ANOTHER_FILE_OPENED;

            return false;
        }

        if (!empty($file)) {
            $this->fp = @fopen($file, 'w+');
        } else {
            $this->error = self::ERR_INVALID_FILE_NAME;

            return false;
        }

        if ($this->fp == false) {
            $this->error = self::ERR_UNABLE_OPEN_CREATE_FILE;

            return false;
        }

        fwrite($this->fp, $this->_header());

        return $this->fp;
    }

    /**
     *	\brief	Fecha o arquivo.
     */
    public function close()
    {
        if (!is_null($this->state)) {
            $this->error = self::ERR_NO_FILE_OPENED;

            return false;
        }

        if ($this->newRow) {
            fwrite($this->fp, '</tr>');
            $this->newRow = false;
        }

        fwrite($this->fp, $this->_footer());
        fclose($this->fp);
        $this->fp = null;

        return true;
    }

    /**
     *	\brief Retorna o cabeçalho do arquivo Excel.
     *
     *	@Params : Void
     *
     *	@return : Void
     */
    private function _header()
    {
        $header = '<html xmlns:o="urn:schemas-microsoft-com:office:office" '
                . 'xmlns:x="urn:schemas-microsoft-com:office:excel" '
                . 'xmlns="http://www.w3.org/TR/REC-html40">'
                . '<head>'
                . '<meta http-equiv=Content-Type content="text/html; charset=utf-8">'
                . '<meta name=ProgId content=Excel.Sheet>'
                . '<!--[if gte mso 9]><xml>'
                . '<o:DocumentProperties>'
                . '<o:LastAuthor>Sriram</o:LastAuthor>'
                . '<o:LastSaved>2005-01-02T07:46:23Z</o:LastSaved>'
                . '<o:Version>10.2625</o:Version>'
                . '</o:DocumentProperties>'
                . '<o:OfficeDocumentSettings>'
                . '<o:DownloadComponents/>'
                . '</o:OfficeDocumentSettings>'
                . '</xml><![endif]-->'
                . '<style>'
                . '<!--table {mso-displayed-decimal-separator:"\."; mso-displayed-thousand-separator:"\,";} '
                . '@page {margin:1.0in .75in 1.0in .75in; mso-header-margin:.5in; mso-footer-margin:.5in;} '
                . 'tr {mso-height-source:auto;} '
                . 'col {mso-width-source:auto;} '
                . 'br {mso-data-placement:same-cell;} '
                . '.style0 {mso-number-format:General; text-align:general; vertical-align:bottom; white-space:nowrap; mso-rotate:0; mso-background-source:auto; mso-pattern:auto; color:windowtext; font-size:10.0pt; font-weight:400; font-style:normal; text-decoration:none; font-family:Arial; mso-generic-font-family:auto; mso-font-charset:0; border:none; mso-protection:locked visible; mso-style-name:Normal; mso-style-id:0;} '
                . 'td {mso-style-parent:style0; padding-top:1px; padding-right:1px; padding-left:1px; mso-ignore:padding; color:windowtext; font-size:10.0pt; font-weight:400; font-style:normal; text-decoration:none; font-family:Arial; mso-generic-font-family:auto; mso-font-charset:0; mso-number-format:General; text-align:general; vertical-align:bottom; border:none; mso-background-source:auto; mso-pattern:auto; mso-protection:locked visible; white-space:nowrap; mso-rotate:0;}'
                . '.xl24 {mso-style-parent:style0; white-space:normal;} -->'
                . '</style>'
                . '<!--[if gte mso 9]>'
                . '<xml>'
                . '<x:ExcelWorkbook>'
                . '<x:ExcelWorksheets>'
                . '<x:ExcelWorksheet>'
                . '<x:Name>' . $this->nameFile . '</x:Name>'
                . '<x:WorksheetOptions>'
                . '<x:Selected/>'
                . '<x:ProtectContents>False</x:ProtectContents>'
                . '<x:ProtectObjects>False</x:ProtectObjects>'
                . '<x:ProtectScenarios>False</x:ProtectScenarios>'
                . '</x:WorksheetOptions>'
                . '</x:ExcelWorksheet>'
                . '</x:ExcelWorksheets>'
                . '<x:WindowHeight>10005</x:WindowHeight>'
                . '<x:WindowWidth>10005</x:WindowWidth>'
                . '<x:WindowTopX>120</x:WindowTopX>'
                . '<x:WindowTopY>135</x:WindowTopY>'
                . '<x:ProtectStructure>False</x:ProtectStructure>'
                . '<x:ProtectWindows>False</x:ProtectWindows>'
                . '</x:ExcelWorkbook>'
                . '</xml><![endif]-->'
                . '</head>'
                . '<body link=blue vlink=purple>'
                . '<table x:str border=0 cellpadding=0 cellspacing=0 style="border-collapse: collapse;table-layout:fixed;">';

        return $header;
    }

    /**
     *	\brief Retorna o rodapé do arquivo Exscel.
     */
    private function _footer()
    {
        return '</table></body></html>';
    }

    /**
     *	\brief Escreve uma linha de título e define os tipos das colunas.
     */
    public function writeHeader($columns)
    {
        if (is_null($this->fp)) {
            $this->error = self::ERR_NO_FILE_OPENED;

            return false;
        }

        if (!is_array($columns)) {
            $this->error = self::ERR_INVALID_ARGUMENT_ARRAY;

            return false;
        }

        $this->columns = [];
        $this->openRow();
        foreach ($columns as $column) {
            if (!is_array($column) || !isset($column['name']) || !isset($column['title']) || !isset($column['type'])) {
                $this->closeRow();
                $this->error = self::ERR_INVALID_ARGUMENT_ARRAY;

                return false;
            }

            $this->columns[$column['name']] = $column;
            $this->addCol($column['name']);
        }
        $this->closeRow();
        $this->newRow = false;
    }

    /**
     *	\brief Escreve uma linha no arquivo a partir de um array de colunas.
     *
     *	@Params : $line_arr: An valid array
     *	@Return : Void
     */
    public function writeLine($line_arr)
    {
        if (is_null($this->fp)) {
            $this->error = self::ERR_NO_FILE_OPENED;

            return false;
        }

        if (!is_array($line_arr)) {
            $this->error = self::ERR_INVALID_ARGUMENT_ARRAY;

            return false;
        }

        $this->openRow();
        foreach ($line_arr as $index => $column) {
            $this->addCol($column, $index);
        }
        $this->closeRow();
        $this->newRow = false;
    }

    /**
     *	\brief Abre uma nova linha na tabela.
     *
     *	@Return : Void
     */
    public function openRow()
    {
        if (is_null($this->fp)) {
            $this->error = self::ERR_NO_FILE_OPENED;

            return false;
        }

        if ($this->newRow) {
            fwrite($this->fp, '</tr><tr>');
        } else {
            fwrite($this->fp, '<tr>');
            $this->newRow = true;
        }
    }

    /**
     *	\brief Fecha uma linha.
     *
     *	@Return : Void
     */
    public function closeRow()
    {
        if (is_null($this->fp)) {
            $this->error = self::ERR_NO_FILE_OPENED;

            return false;
        }

        if ($this->newRow) {
            fwrite($this->fp, '</tr>');
            $this->newRow = false;
        }
    }

    /**
     *	\brief Adiciona uma coluna na tabela.
     *
     *	@Params : $value : Coloumn Value
     *	@Return : Void
     */
    public function addCol($value, $column = null)
    {
        if (is_null($this->fp)) {
            $this->error = self::ERR_NO_FILE_OPENED;

            return false;
        }

        if (is_null($column) || !isset($this->columns[$column])) {
            fwrite($this->fp, '<td class="xl24" width="64"' . (is_numeric($value) ? ' x:num' : '') . '>' . $value . '</td>');
        } else {
            fwrite($this->fp, '<td class="xl24" width="' . (empty($this->columns[$column]['width']) ? '64' : $this->columns[$column]['width']) . '" x:' . $this->columns[$column]['type'] . '>' . $value . '</td>');
        }
    }
}
