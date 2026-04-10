#include <HTTPClient.h>
#include <WiFi.h>

#include <Wire.h>
#include "rgb_lcd.h"

rgb_lcd lcd; // écran lcd
int nombre_personne = 0; // compte le nombre de personnes
unsigned long temps_passe = millis(); // temps passé depuis 
unsigned char colore = 0;

#define CAPTEUR_1 25
#define CAPTEUR_2 26
#define EAP_IDENTITY "login"           //if connecting from another corporation, use identity@organization.domain in Eduroam
#define EAP_USERNAME "uapv2601260"           //oftentimes just a repeat of the identity
#define EAP_PASSWORD "c4M!LLeL€Ng@lardon61"        //your Eduroam password
const char *ssid = "eduroam";          
int lastC1 = 0; 
int lastC2 = 0;
int entry = 0;
unsigned long timer = 0; 

String serveur = "http://10.126.5.73/compteur/api.php"; 

void setup() {
  Serial.begin(115200);
  
  // Connexion au WiFi
  WiFi.begin(ssid, WPA2_AUTH_PEAP, EAP_IDENTITY, EAP_USERNAME, EAP_PASSWORD);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi connecté !");
  Serial.print("Adresse IP de l'ESP32 : ");
  Serial.println(WiFi.localIP());
  Serial.println("adrresse mac:");
  Serial.println(WiFi.macAddress());
  pinMode(CAPTEUR_1, INPUT);
  pinMode(CAPTEUR_2, INPUT);
  Serial.println("Capteurs prêts");




  lcd.begin(16, 2);
  lcd.setRGB(0, 0, 0);
  
}
void envoyerCommande(String action) {
  if(WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    

    String urlComplete = serveur + "?action=" + action + "&id=" + WiFi.macAddress();
    
    http.begin(urlComplete); 
    int httpResponseCode = http.GET(); // On envoie la requête
    
    if (httpResponseCode > 0) {
      Serial.print("Réponse du serveur : ");
      Serial.println(httpResponseCode);
    } else {
      Serial.print("Erreur de requête : ");
      Serial.println(httpResponseCode);
    }
    
    http.end(); // On libère les ressources
  }
  temps_passe = millis();
}
void loop() {
  int c1 = digitalRead(CAPTEUR_1);
  int c2 = digitalRead(CAPTEUR_2);

  // 1. DÉTECTION DU PREMIER CAPTEUR (Début du mouvement)
  if (c1 == 1 && lastC1 == 0 && entry == 0) {
      entry = 1;  
      timer = millis();
  }
  if (c2 == 1 && lastC2 == 0 && entry == 0) {
      entry = 2;  
      timer = millis(); 
  }

  // 2. VALIDATION (Si on touche le 2ème capteur AVANT le timeout)
  if(c1 == 1 && lastC1 == 0 && entry == 2) {
    Serial.println("entrée");

    lcd.setCursor(0, 0);
    lcd.print("passage!!");
    nombre_personne++;
    lcd.setCursor(0, 1);
    lcd.print(nombre_personne);
    lcd.print(" personnes");

    lcd.setRGB(0, 255, 0);
    colore = 1;


    envoyerCommande("plus");

    entry = 0; // On a réussi, on reset
  }
  if(c2 == 1 && lastC2 == 0 && entry == 1) {
    Serial.println("sortie");

    lcd.setCursor(0, 0);
    lcd.print("depassage!!");
    nombre_personne--;
    lcd.setCursor(0, 1);
    lcd.print(nombre_personne);
    lcd.print(" personnes");

    lcd.setRGB(255, 0, 0);
    colore = 1;


    envoyerCommande("moins");
    
    entry = 0; // On a réussi, on reset
  }

  // 3. LE TIMEOUT (On efface seulement si on a attendu trop longtemps)
  // On met 1000ms (1 seconde) pour laisser le temps de marcher
  if (entry != 0 && (millis() - timer > 1000)) {
      entry = 0;
      Serial.println("Annulé : trop long ou demi-tour");
  }

  lastC1 = c1;
  lastC2 = c2;

  if (colore == 1 && millis() - temps_passe > 1000){
    lcd.setRGB(0, 0, 0);
    temps_passe = millis();
    colore = 0;
  }
}