# Configurações da classe de tratamento de URI/URL

O arquivo `\conf\uri.php` e as variantes para ambientes
`\conf\${enviroment}\uri.php` armazenam as entradas de configuração para
tratamento de URL.

As entradas de configuração dos arquivos *uri*, são utilizadas pela classe URI,
sendo que as entradas previamente definidas não podem ser omitidas sob risco de
dano à aplicação.

Você poderá adicionar entradas personalizadas de sua aplicação.

## Entradas de configuração:

-   **'system_root'** - URI da página inicial;
-   **'common_urls'** - Essa entrada é um array de URLs comuns da aplicação que
irão gerar variáveis de template.;
-   **'ignored_segments'** - Quantidade de segmentos iniciais que devem ser
ignorados na descoberta da controladora. Útil para sites onde alguns dos
segmentos iniciais servem para algum parâmetro a ser passado para a aplicação,
como por exemplo para determinar idioma, localização ou setor.
Por exemplo: http://www.seusite.com/pt-br/pagina
-   **'dynamic'** - Host do conteúdo dinâmico do site.
Ex.: 'http://www.seusite.com'
-   **'static'** - Host do conteúdo estático do site.
Ex.: 'http://cdn.seusite.com'
-   **'secure'** - Host seguro do site.
Ex.: 'https://www.seusite.com'

### Entrada 'common_urls':

Cada entrada do array contém uma variável de template, onde o índice é uma
string contendo o nome da variável e o valor é um array podendo conter de um a
todos os parâmetros necessários para construção da URL, conforme a ordem do
método buildURL da classe URI.

Logo: cada índice do array conterá a seguinte informação, respectivamente:

-   índice 0 (zero) => array de segmentos da URL;
-   índice 1 => array da query string;
-   índice 2 => boleano para o parâmetro forceRewrite;
-   índice 3 => entrada de configuração host;
-   índice 4 => booleano para o parânetro include_ignores_segments.

#### Exemplos:

No exemplo a seguir, a aplicação irá executar `Url1::setCommon()`:

```php
$conf = [
    'register_method_set_common_urls' => [
        'class' => 'Url1',
        'method' => 'setCommon',
        'static' => true
       ],
];
```

No exemplo a seguir, a aplicação irá executar `(new Urls())->setCommon()`:

```php
$conf = [
    'register_method_set_common_urls' => [
        'class' => 'Urls',
        'method' => 'setCommon',
        'static' => false
       ],
];
```

No exemplo a seguir, a aplicação irá executar `new Urls()`:

```php
$conf = [
    'register_method_set_common_urls' => [
        'class' => 'Urls',
        'static' => false
    ],
];
```
