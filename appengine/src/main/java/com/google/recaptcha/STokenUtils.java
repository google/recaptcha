package com.google.recaptcha;

import com.google.common.io.BaseEncoding;
import com.google.gson.Gson;
import com.google.gson.JsonObject;

import java.io.UnsupportedEncodingException;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.Arrays;
import java.util.UUID;

import javax.crypto.Cipher;
import javax.crypto.spec.SecretKeySpec;

/**
 * Example code to add stoken parameter for reCAPTCHA.
 */
public class STokenUtils {
  private static final String CIPHER_INSTANCE_NAME = "AES/ECB/PKCS5Padding";

  public static final String createSToken(String siteSecret) {
    String sessionId = UUID.randomUUID().toString();
    String jsonToken = createJsonToken(sessionId);
    return encryptAes(jsonToken, siteSecret);
  }

  private static final String createJsonToken(String sessionId) {
    JsonObject obj = new JsonObject();
    obj.addProperty("session_id", sessionId);
    obj.addProperty("ts_ms", System.currentTimeMillis());
    return new Gson().toJson(obj);
  }

  private static String encryptAes(String input, String siteSecret) {
    try {
      SecretKeySpec secretKey = getKey(siteSecret);
      Cipher cipher = Cipher.getInstance(CIPHER_INSTANCE_NAME);
      cipher.init(Cipher.ENCRYPT_MODE, secretKey);
      return BaseEncoding.base64Url().omitPadding().encode(cipher.doFinal(input.getBytes("UTF-8")));
    } catch (Exception e) {
      e.printStackTrace();
    }
    return null;
  }

  private static SecretKeySpec getKey(String siteSecret){
    try {
      byte[] key = siteSecret.getBytes("UTF-8");
      key = Arrays.copyOf(MessageDigest.getInstance("SHA").digest(key), 16);
      return new SecretKeySpec(key, "AES");
    } catch (NoSuchAlgorithmException | UnsupportedEncodingException e) {
      e.printStackTrace();
    }
    return null;
  }

  public static void main(String [] args) {
    String sessionId = UUID.randomUUID().toString();
    String siteSecret = "12345678";
    String jsonToken = createJsonToken(sessionId);

    System.out.println("\n                                                                 ");
    System.out.println("            _____          _____ _______ _____ _    _          ");
    System.out.println("           / ____|   /\\   |  __ \\__   __/ ____| |  | |   /\\     ");
    System.out.println("  _ __ ___| |       /  \\  | |__) | | | | |    | |__| |  /  \\     ");
    System.out.println(" | '__/ _ \\ |      / /\\ \\ |  ___/  | | | |    |  __  | / /\\ \\ ");
    System.out.println(" | | |  __/ |____ / ____ \\| |      | | | |____| |  | |/ ____ \\   ");
    System.out.println(" |_|  \\___|\\_____/_/    \\_\\_|      |_|  \\_____|_|  |_/_/    \\_\\ ");
    System.out.println("\n Demo for stoken generation.\n                                   ");

    System.out.println(" Session Id: " + sessionId);
    System.out.println(" json token: " + jsonToken);
    System.out.println(" siteSecret: " + siteSecret);
    System.out.println(" Encrypted stoken: " + encryptAes(jsonToken, siteSecret));
    System.out.println("\n");
  }
}
