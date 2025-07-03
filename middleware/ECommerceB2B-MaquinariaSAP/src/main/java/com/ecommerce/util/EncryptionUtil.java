package com.ecommerce.util;

import javax.crypto.Cipher;
import javax.crypto.spec.SecretKeySpec;
import javax.crypto.spec.IvParameterSpec;
import java.security.MessageDigest;
import java.util.Arrays;

/**
 * Utilidad para cifrar/descifrar datos usando AES.
 */
public class EncryptionUtil {

    /**
     * Cifra los datos proporcionados utilizando AES-128-CBC con clave derivada de la frase proporcionada.
     * @param key Frase o clave para derivar la llave de cifrado.
     * @param data Datos en bytes a cifrar.
     * @return Datos cifrados en bytes.
     * @throws Exception si ocurre un error durante el cifrado.
     */
    public static byte[] encrypt(String key, byte[] data) throws Exception {
        if (key == null || key.isEmpty()) {
            throw new IllegalArgumentException("Encryption key must not be empty");
        }
        // Derivar una llave de 128 bits (16 bytes) y un IV de 128 bits del hash SHA-256 de la clave
        MessageDigest sha = MessageDigest.getInstance("SHA-256");
        byte[] hash = sha.digest(key.getBytes("UTF-8"));
        byte[] aesKey = Arrays.copyOfRange(hash, 0, 16);
        byte[] iv     = Arrays.copyOfRange(hash, 16, 32);
        // Configurar cipher AES CBC con padding PKCS5
        Cipher cipher = Cipher.getInstance("AES/CBC/PKCS5Padding");
        SecretKeySpec secretKey = new SecretKeySpec(aesKey, "AES");
        IvParameterSpec ivSpec  = new IvParameterSpec(iv);
        cipher.init(Cipher.ENCRYPT_MODE, secretKey, ivSpec);
        return cipher.doFinal(data);
    }

    /**
     * Descifra los datos proporcionados utilizando AES-128-CBC con clave derivada de la misma frase utilizada en cifrado.
     * @param key Frase o clave utilizada para cifrar (debe ser la misma para descifrar).
     * @param encryptedData Datos cifrados en bytes.
     * @return Datos originales descifrados en bytes.
     * @throws Exception si ocurre un error durante el descifrado.
     */
    public static byte[] decrypt(String key, byte[] encryptedData) throws Exception {
        if (key == null || key.isEmpty()) {
            throw new IllegalArgumentException("Encryption key must not be empty");
        }
        // Derivar llave e IV del hash SHA-256 de la clave
        MessageDigest sha = MessageDigest.getInstance("SHA-256");
        byte[] hash = sha.digest(key.getBytes("UTF-8"));
        byte[] aesKey = Arrays.copyOfRange(hash, 0, 16);
        byte[] iv     = Arrays.copyOfRange(hash, 16, 32);
        Cipher cipher = Cipher.getInstance("AES/CBC/PKCS5Padding");
        SecretKeySpec secretKey = new SecretKeySpec(aesKey, "AES");
        IvParameterSpec ivSpec  = new IvParameterSpec(iv);
        cipher.init(Cipher.DECRYPT_MODE, secretKey, ivSpec);
        return cipher.doFinal(encryptedData);
    }
}
