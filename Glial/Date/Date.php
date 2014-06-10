class Date
{


/**
 * Cette fonction retourne un tableau de timestamp correspondant
 * aux jours fériés en France pour une année donnée.
 * @author Zéfling
 * @param $year int L'année (si null prend d'année courante. Défaut : null)
 * @return array:int la liste timestamp des jours fériés
 */
function getFranceHolidays($year = null) {
	if ($year === null) {
		$year = intval(date('Y'));
	}
 
	// Dimanche de Pâques
	$easterDate  = easter_date($year);
	$easterDay   = date('j', $easterDate);
	$easterMonth = date('n', $easterDate);	
	$holidays = array(
		// Dates fixes
		mktime(0, 0, 0, 1,  1,  $year),  // 1er janvier
		mktime(0, 0, 0, 5,  1,  $year),  // Fête du travail
		mktime(0, 0, 0, 5,  8,  $year),  // Victoire des alliés
		mktime(0, 0, 0, 7,  14, $year),  // Fête nationale
		mktime(0, 0, 0, 8,  15, $year),  // Assomption
		mktime(0, 0, 0, 11, 1,  $year),  // Toussaint
		mktime(0, 0, 0, 11, 11, $year),  // Armistice
		mktime(0, 0, 0, 12, 25, $year),  // Noël
 
		// Dates variables
		mktime(0, 0, 0, $easterMonth, $easterDay + 1,  $year), // lundi de Pâques
		mktime(0, 0, 0, $easterMonth, $easterDay + 39, $year), // jeudi de l'Ascension
		mktime(0, 0, 0, $easterMonth, $easterDay + 50, $year), // lundi de Pentecôte
	);
	sort($holidays);
 
	return $holidays;
}






}
