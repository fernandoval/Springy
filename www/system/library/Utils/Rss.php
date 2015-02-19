<?php
/**	\file
 *	FVAL PHP Framework for Web Applications
 *
 *  \copyright	Copyright (c) 2007-2015 FVAL Consultoria e Informática Ltda.\n
 *  \copyright	Copyright (c) 2007-2015 Fernando Val\n
 *
 *	\brief		Classe para criação de XML de RSS
 *	\warning	Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version	1.5.6
 *  \author		Fernando Val  - fernando.val@gmail.com
 *	\ingroup	framework
 */

namespace FW\Utils;

require_once $GLOBALS['SYSTEM']['3RDPARTY_PATH'] . DIRECTORY_SEPARATOR . 'feedcreator' . DIRECTORY_SEPARATOR . 'feedcreator.class.php';

/**
 *  \brief Classe para criação de XML de RSS
 */
class Rss
{
	private static $rss = NULL;
	private static $image = NULL;

	/**
	 *	\brief Construtor da classe
	 */
	function __construct($title='', $desc='', $link='', $syndcationURL='')
	{
		self::$rss = new \UniversalFeedCreator();
		self::$rss->useCached();
		self::$rss->link = (empty($link) ? 'http://'.$_SERVER['HTTP_HOST'] : $link);
		self::$rss->syndicationURL = (empty($syndcationURL) ? 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'] : $syndcationURL);
		self::$rss->encoding = $GLOBALS['SYSTEM']['CHARSET'];
		if ($title)	{
			self::title($title);
		}
		if ($desc) {
			self::description($desc);
		}
	}

	/**
	 *	\brief Define o título do feed
	 */
	public function title($value)
	{
		self::$rss->title = $value;
	}

	/**
	 *	\brief Define a descrição do feed
	 */
	public function description($value, $truncateSize=0)
	{
		self::$rss->description = $value;
		if ($truncateSize) {
			self::$rss->descriptionTruncSize = $truncateSize;
		}
	}

	/**
	 *	\brief
	 */
	public function descriptionHtmlSynticated($value)
	{
		self::$rss->descriptionHtmlSyndicated = $value;
	}

	/**
	 *	\brief Define a descrição do feed
	 */
	public function link($value)
	{
		self::$rss->link = $value;
	}

	/**
	 *	\brief Define a imagem do feed
	 */
	public function image($imageUrl, $link, $title='', $description='')
	{
		$image = new \FeedImage();
		$image->title = $title;
		$image->url = $imageUrl;
		$image->link = $link;
		$image->description = $description;
		self::$rss->image = $image;
	}

	/**
	 *	\brief Define o charset do feed
	 */
	public function setEncoding($value)
	{
		self::$rss->encoding = $value;
	}

	/**
	 *	\brief Adiciona um item ao feed
	 */
	public function addItem($title, $link, $description, $date, $category, $autor, $source='')
	{
		$item = new \FeedItem();
		$item->title = html_entity_decode(htmlspecialchars($title));
		$item->link  = $link;
		$item->description = html_entity_decode($description);
		$item->date = $date;
		$item->source = (empty($source) ? 'http://'.$_SERVER['HTTP_HOST'] : $source);
		$item->category = $category;
		$item->author = $autor;

		self::$rss->addItem($item);
	}

	/**
	 *	\brief Salva o arquivo do feed
	 *
	 *	@param[in] $file Nome do arquivo de feed.
	 *	@param[in] $format Formato do feed.
	 *		Os seguintes valores são aceitáveis: 'RSS2.0', 'RSS0.91', 'RSS1.0', 'ATOM0.3', 'OPML'
	 */
	public function save($file, $format='RSS2.0')
	{
		$validFormats = array('RSS2.0', 'RSS0.91', 'RSS1.0', 'ATOM0.3', 'OPML');

		if (!in_array($format, $validFormats)) {
			$format = $validFormats[0];
		}

		if ( ( substr($file, -4) != 'xml') ) {
			$file .= '.xml';
		}

		self::$rss->saveFeed($format, $file);
	}
}