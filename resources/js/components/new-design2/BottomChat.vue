<template>
  <div v-if="generalSettings.support_chat">
    <!-- Chat Button -->
    <button :class="['lv-chat-button', chatWindowOpen ? 'd-none' : 'd-none d-lg-block']" type="button"
      @click.stop="openChatWindow">
      <span class="lv-chat-button-content">
        <img :src="static_asset('/assets/img/chat.svg')" height="20" class="lv-chat-icon" />
        <span class="lv-chat-button-text">{{ $t("talk_with_us") }}</span>
      </span>
    </button>

    <!-- Chat Window -->
    <div :class="['lv-chat-window', chatWindowOpen ? 'lv-chat-window-open' : 'lv-chat-window-closed']">
      <!-- Header -->
      <div class="lv-chat-header">
        <div class="lv-chat-header-content">
          <img :src="generalSettings.chat.customer_chat_logo" class="lv-chat-logo" />
          <span class="lv-chat-title">{{ generalSettings.chat.customer_chat_name }}</span>
        </div>
        <button class="lv-chat-close" type="button" @click.stop="closeChatWindow">
          <i class="la la-times"></i>
        </button>
      </div>

      <!-- Chat Messages (Authenticated) -->
      <div v-if="isAuthenticated" class="lv-chat-messages c-scrollbar">
        <ul ref="chatList" class="lv-message-list">
          <li v-for="(message, i) in messages" :key="i" class="lv-message-item">
            <div class="lv-message-time">{{ message.time }}</div>
            <div v-if="message.user_id == currentUser.id" class="lv-message-wrapper lv-message-own">
              <div class="lv-message-bubble lv-message-bubble-own">{{ message.message }}</div>
            </div>
            <div v-else class="lv-message-wrapper lv-message-other">
              <v-avatar size="32" class="lv-message-avatar">
                <img alt="Avatar" :src="generalSettings.chat.customer_chat_logo" />
              </v-avatar>
              <div class="lv-message-bubble lv-message-bubble-other">{{ message.message }}</div>
            </div>
          </li>
        </ul>
      </div>

      <!-- Login Prompt (Not Authenticated) -->
      <div v-else class="lv-chat-login c-scrollbar">
        <img :src="static_asset('/assets/img/chat-login.png')" class="lv-login-avatar" />
        <div class="lv-login-text">
          {{ $t("you_have_to") }}
          <router-link :to="{ name: 'Login' }" class="lv-login-link">{{ $t("login") }}</router-link>
          {{ $t("or") }}
          <router-link :to="{ name: 'Registration' }" class="lv-login-link">{{ $t("register") }}</router-link>
          {{ $t("as_a_customer_to_contact_us") }}
        </div>
      </div>

      <!-- Input Area -->
      <div class="lv-chat-input-area">
        <v-form class="lv-chat-form" @submit.prevent="sendMessage">
          <v-row no-gutters align="center" class="lv-input-row">
            <v-col>
              <v-text-field 
                v-model="chat.message" 
                variant="plain" 
                flat 
                hide-details
                :placeholder="$t('type_message')"
                class="lv-text-field"
              ></v-text-field>
            </v-col>
            <v-col cols="auto">
              <v-btn 
                size="small" 
                type="submit" 
                icon 
                class="lv-send-btn" 
                :disabled="sending || !chat.message"
                @click.native="sendMessage"
              >
                <i class="las la-paper-plane"></i>
              </v-btn>
            </v-col>
          </v-row>
        </v-form>
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters, mapMutations } from "vuex";
export default {
  data: () => ({
    sending: false,
    messages: [],
    chat: {
      message: "",
    },
  }),
  computed: {
    ...mapGetters("auth", ["chatWindowOpen", "currentUser", "isAuthenticated"]),
    ...mapGetters("app", ["generalSettings"]),
  },
  watch: {
    chatWindowOpen(newValue) {
      if (newValue && this.isAuthenticated) {
        this.getOldChats();
        this.getNewMessages();
      }
    },
  },
  methods: {
    ...mapMutations("auth", ["updateChatWindow"]),
    openChatWindow() {
      this.updateChatWindow(true);
      if (this.isAuthenticated) {
        this.getOldChats();
        this.getNewMessages();
      }
    },
    closeChatWindow() {
      this.updateChatWindow(false);
    },
    async sendMessage() {
      this.sending = true;
      if (this.isAuthenticated && this.chat.message) {
        const res = await this.call_api("post", "user/chats/send", this.chat);
        if (res.data.success) {
          this.chat.message = "";
          this.messages.push(res.data.data);
          this.chatScrollToBottom();
        } else {
          this.snack({ message: res.data.message });
        }
        this.sending = false;
      }
    },
    async getOldChats() {
      const res = await this.call_api("get", "user/chats");
      if (res.data.success) {
        this.messages = res.data.data.data;
        this.chatScrollToBottom();
      }
    },
    chatScrollToBottom() {
      setTimeout(() => {
        const el = this.$refs.chatList?.lastElementChild;
        if (el) {
          el.scrollIntoView({ behavior: "smooth" });
        }
      }, 100);
    },
    getNewMessages() {
      setInterval(async () => {
        const res = await this.call_api("get", "user/chats/new-messages");
        if (res.data.success && res.data.data.data.length > 0) {
          this.messages = [...this.messages, ...res.data.data.data];
          this.chatScrollToBottom();
        }
      }, 5000);
    },
  },
  created() {
    if (this.isAuthenticated && this.chatWindowOpen) {
      this.getOldChats();
      this.getNewMessages();
    }
  },
};
</script>

<style scoped>
/* Refined Luxury Chat Styling */

/* Chat Button */
.lv-chat-button {
  position: fixed;
  bottom: 28px;
  right: 28px;
  background: #000000;
  color: #ffffff;
  border: none;
  border-radius: 50px;
  padding: 14px 28px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-family: 'Helvetica Neue', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  z-index: 999;
}

.lv-chat-button:hover {
  background: #1a1a1a;
  box-shadow: 0 6px 28px rgba(0, 0, 0, 0.25);
  transform: translateY(-2px);
}

.lv-chat-button-content {
  display: flex;
  align-items: center;
  gap: 10px;
}

.lv-chat-icon {
  filter: brightness(0) invert(1);
}

.lv-chat-button-text {
  font-size: 14px;
  font-weight: 500;
  letter-spacing: 0.3px;
}

/* Chat Window */
.lv-chat-window {
  position: fixed;
  bottom: 28px;
  right: 28px;
  width: 400px;
  background: #ffffff;
  border-radius: 16px;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15);
  font-family: 'Helvetica Neue', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
  z-index: 1000;
  overflow: hidden;
}

.lv-chat-window-open {
  display: flex;
  flex-direction: column;
  height: 600px;
  opacity: 1;
}

.lv-chat-window-closed {
  display: none;
  height: 0;
  opacity: 0;
}

/* Chat Header */
.lv-chat-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  background: #000000;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.lv-chat-header-content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.lv-chat-logo {
  width: 32px;
  height: 32px;
  object-fit: contain;
  filter: brightness(0) invert(1);
}

.lv-chat-title {
  font-size: 15px;
  font-weight: 600;
  color: #ffffff;
  letter-spacing: 0.3px;
}

.lv-chat-close {
  background: transparent;
  border: none;
  color: #ffffff;
  cursor: pointer;
  padding: 8px;
  transition: opacity 0.2s;
  font-size: 20px;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.lv-chat-close:hover {
  opacity: 0.7;
  background: rgba(255, 255, 255, 0.1);
}

/* Chat Messages Area */
.lv-chat-messages {
  flex: 1;
  overflow-y: auto;
  background: #fafafa;
  padding: 24px;
}

.lv-message-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.lv-message-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.lv-message-time {
  font-size: 11px;
  color: #999999;
  text-align: center;
  letter-spacing: 0.3px;
  font-weight: 500;
}

.lv-message-wrapper {
  display: flex;
  align-items: flex-end;
  gap: 10px;
}

.lv-message-own {
  justify-content: flex-end;
  padding-left: 48px;
}

.lv-message-other {
  justify-content: flex-start;
  padding-right: 48px;
}

.lv-message-bubble {
  padding: 12px 16px;
  font-size: 14px;
  line-height: 1.5;
  letter-spacing: 0.2px;
  max-width: 100%;
  word-wrap: break-word;
  border-radius: 16px;
}

.lv-message-bubble-own {
  background: #000000;
  color: #ffffff;
  border-radius: 16px 16px 4px 16px;
}

.lv-message-bubble-other {
  background: #ffffff;
  color: #000000;
  border: 1px solid #e5e5e5;
  border-radius: 16px 16px 16px 4px;
}

.lv-message-avatar {
  flex-shrink: 0;
  border-radius: 50%;
  overflow: hidden;
}

/* Login Prompt */
.lv-chat-login {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 32px;
  background: #fafafa;
  text-align: center;
}

.lv-login-avatar {
  width: 72px;
  height: 72px;
  margin-bottom: 24px;
  opacity: 0.9;
  border-radius: 50%;
}

.lv-login-text {
  font-size: 14px;
  line-height: 1.7;
  color: #333333;
  letter-spacing: 0.2px;
}

.lv-login-link {
  color: #000000;
  text-decoration: none;
  font-weight: 600;
  border-bottom: 1.5px solid #000000;
  transition: opacity 0.2s;
  padding-bottom: 1px;
}

.lv-login-link:hover {
  opacity: 0.6;
}

/* Input Area */
.lv-chat-input-area {
  padding: 20px;
  background: #ffffff;
  border-top: 1px solid #e5e5e5;
}

.lv-chat-form {
  background: #fafafa;
  border: 1.5px solid #e5e5e5;
  border-radius: 24px;
  overflow: hidden;
  transition: all 0.2s;
}

.lv-chat-form:focus-within {
  border-color: #000000;
  background: #ffffff;
}

.lv-input-row {
  align-items: center;
}

.lv-text-field {
  padding: 0 18px;
  font-size: 14px;
  letter-spacing: 0.2px;
}

.lv-text-field ::v-deep(.v-field__input) {
  padding: 14px 0;
  min-height: auto;
}

.lv-text-field ::v-deep(input::placeholder) {
  color: #999999;
  opacity: 1;
}

.lv-send-btn {
  background: #000000 !important;
  color: #ffffff !important;
  box-shadow: none !important;
  margin-right: 6px;
  width: 40px;
  height: 40px;
  border-radius: 50% !important;
  transition: all 0.2s;
  min-width: 40px !important;
}

.lv-send-btn:hover:not(:disabled) {
  background: #1a1a1a !important;
  transform: scale(1.05);
}

.lv-send-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.lv-send-btn i {
  font-size: 18px;
}

/* Scrollbar Styling */
.c-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.c-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.c-scrollbar::-webkit-scrollbar-thumb {
  background: #d0d0d0;
  border-radius: 3px;
}

.c-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #b0b0b0;
}

/* Responsive */
@media (max-width: 768px) {
  .lv-chat-window {
    width: calc(100vw - 32px);
    right: 16px;
    bottom: 16px;
    border-radius: 12px;
  }

  .lv-chat-button {
    right: 16px;
    bottom: 16px;
    padding: 12px 24px;
  }

  .lv-chat-button-text {
    font-size: 13px;
  }

  .lv-message-own {
    padding-left: 40px;
  }

  .lv-message-other {
    padding-right: 40px;
  }
}
</style>