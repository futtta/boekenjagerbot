/*
* haal alle unique gemeenteid's uit de subscribers tabel (-> A)
* voor elke gemeenteid in (A):
* - haal gemeente-naam op uit gemeente tabel (-> A2)
* - haal alle susbscribers uit de subscribers tabel (-> B)
* - bevraag de boekenzoekers API (gemeente=A2&output=json&timeFrom=<timestamp laatste check>) (-> C)
* - voor elk resultaat (C) stuur een bericht naar elke subscriber (B) 
*/
