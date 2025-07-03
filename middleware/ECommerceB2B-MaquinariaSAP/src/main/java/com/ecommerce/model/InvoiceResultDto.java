// src/main/java/com/ecommerce/model/InvoiceResultDto.java
package com.ecommerce.model;

import java.util.List;

public class InvoiceResultDto {
    private String orderNumber;
    private String invoiceNumber;
    private String status; // "COMPLETADO" o "ERROR"
    private List<Message> messages;

    public InvoiceResultDto() {
    }

    public InvoiceResultDto(String orderNumber, String invoiceNumber, String status, List<Message> messages) {
        this.orderNumber = orderNumber;
        this.invoiceNumber = invoiceNumber;
        this.status = status;
        this.messages = messages;
    }

    public String getOrderNumber() {
        return orderNumber;
    }
    public void setOrderNumber(String orderNumber) {
        this.orderNumber = orderNumber;
    }

    public String getInvoiceNumber() {
        return invoiceNumber;
    }
    public void setInvoiceNumber(String invoiceNumber) {
        this.invoiceNumber = invoiceNumber;
    }

    public String getStatus() {
        return status;
    }
    public void setStatus(String status) {
        this.status = status;
    }

    public List<Message> getMessages() {
        return messages;
    }
    public void setMessages(List<Message> messages) {
        this.messages = messages;
    }

    @Override
    public String toString() {
        return "InvoiceResultDto{" +
               "orderNumber='" + orderNumber + '\'' +
               ", invoiceNumber='" + invoiceNumber + '\'' +
               ", status='" + status + '\'' +
               ", messages=" + messages +
               '}';
    }

    public static class Message {
        private String type;
        private String text;

        public Message() {
        }

        public Message(String type, String text) {
            this.type = type;
            this.text = text;
        }

        public String getType() {
            return type;
        }
        public void setType(String type) {
            this.type = type;
        }

        public String getText() {
            return text;
        }
        public void setText(String text) {
            this.text = text;
        }

        @Override
        public String toString() {
            return "Message{" +
                   "type='" + type + '\'' +
                   ", text='" + text + '\'' +
                   '}';
        }
    }
}
