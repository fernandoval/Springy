CREATE TABLE fcms_article (
	article_id		int	not null auto_increment,
	articls_title	varchar(255) not null,
	article_slug	varchar(200) not null,
	category_id		int,
	article_text	text
)