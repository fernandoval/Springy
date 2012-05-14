<?php

/**
 * Credit Card Validation Solution, PHP Edition,
 * Messages d'erreur français de langue.
 *
 * @package    CreditCardValidationSolution
 * @author     Daniel Convissor <danielc@analysisandsolutions.com>
 * @copyright  The Analysis and Solutions Company, 2002-2006
 * @version    $Name: rel-5-14 $ $Id: ccvs_fr.inc,v 1.4 2006/03/18 17:31:31 danielc Exp $
 * @link       http://www.analysisandsolutions.com/software/ccvs/ccvs.htm
 */

/** */
$CCVSErrNumberString = 'Le numéro n\'est pas une chaîne';
$CCVSErrVisa14       = 'Visa a habituellement 16 ou 13 chiffres, mais vous en avez écrit 14';
$CCVSErrUnknown      = 'Les quatres premiers chiffres, %s, n\'indiquent pas un type de carte connu';
$CCVSErrAccepted     = 'Le programmeur a incorrectement employé l\'argument Accepted';
$CCVSErrNoAccept     = 'Nous n\'acceptons pas les cartes %s';
$CCVSErrShort        = 'Le numéro manque %s chiffre(s)';
$CCVSErrLong         = 'Le numéro a %s chiffre(s) en trop';
$CCVSErrChecksum     = 'Le numéro a échoué à la vérification de somme';
$CCVSErrMonthString  = 'Mois n\'est pas une chaîne';
$CCVSErrMonthFormat  = 'Le mois n\'a pas un format admissible';
$CCVSErrYearString   = 'Année n\'est pas une chaîne';
$CCVSErrYearFormat   = 'L\'année n\'a pas un format admissible';
$CCVSErrExpired      = 'La carte est expirée';
