<script setup>
import { nextTick, onMounted, ref } from "vue";
import { useToast } from "primevue/usetoast";
import { Head } from "@inertiajs/vue3";

import Toast from "primevue/toast";
import { Button } from "primevue";

const toast = useToast();

const prevGuess = ref("rock");
const currentGuess = ref("");
const currentEmoji = ref("🗿");
const currentExplanation = ref("");
const currentGid = ref("");

const prevGuessesList = ref(["rock"]);

const isSendingRequest = ref(false);

const gameLost = ref(false);
const score = ref(0);

const input = ref();

onMounted(() => {
    input.value.focus();
});

function guess() {
    if (currentGuess.value.length === 0 || isSendingRequest.value) return;

    if (prevGuessesList.value.includes(currentGuess.value.toLowerCase())) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No repeated guesses! Try something else.",
            life: 5000,
        });

        return;
    }

    if (currentGid.value.length === 0) {
        currentGid.value = uuidv4();
    }

    isSendingRequest.value = true;

    window.axios
        .post("/guess", {
            gid: currentGid.value,
            prev: prevGuess.value.toLowerCase(),
            guess: currentGuess.value.toLowerCase(),
        })
        .then((response) => {
            if (response.data.win) {
                prevGuess.value = currentGuess.value.toLowerCase();
                prevGuessesList.value.push(prevGuess.value.toLowerCase());

                currentEmoji.value = response.data.emoji;

                score.value++;
            } else {
                gameLost.value = true;
            }

            currentExplanation.value = response.data.explanation;
        })
        .catch((error) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: error.response.data.message ?? error.response.data,
                life: 5000,
            });
        })
        .finally(() => {
            currentGuess.value = "";

            isSendingRequest.value = false;

            nextTick(() => {
                input.value.focus();
            });
        });
}

function handleInput(event) {
    currentGuess.value = currentGuess.value.toLowerCase();
}

function restart() {
    gameLost.value = false;
    score.value = 0;
    prevGuess.value = "rock";
    currentGuess.value = "";
    currentEmoji.value = "🗿";
    currentExplanation.value = "";
    currentGid.value = uuidv4();
    prevGuessesList.value = ["rock"];

    nextTick(() => {
        input.value.focus();
    });
}

function uuidv4() {
    return "10000000-1000-4000-8000-100000000000".replace(/[018]/g, (c) =>
        (
            +c ^
            (crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (+c / 4)))
        ).toString(16)
    );
}
</script>

<template>
    <Toast />

    <Head title="What beats rock" />

    <div class="h-dvh mx-4 sm:mx-32 md:mx-48 lg:mx-72 xl:mx-96">
        <div
            class="flex flex-col pb-48 w-full items-center top-[20%] justify-start relative"
        >
            <p class="text-3xl font-bold">what beats</p>
            <p class="text-2xl text-center">{{ prevGuess }}?</p>

            <p class="w-1/2 mt-2 text-center">{{ currentExplanation }}</p>

            <p class="text-5xl my-5">{{ gameLost ? "❌" : currentEmoji }}</p>

            <form @submit.prevent="guess" v-if="!gameLost">
                <input
                    v-model="currentGuess"
                    @input="handleInput"
                    :disabled="isSendingRequest"
                    ref="input"
                    class="pl-4 py-4 text-lg border border-1-black"
                /><button
                    :disabled="isSendingRequest"
                    type="submit"
                    class="p-4 border border-1-black text-lg bg-green-200"
                >
                    <div v-if="isSendingRequest">
                        <i class="pi pi-spin pi-spinner"></i>
                    </div>
                    <div v-else>GO</div>
                </button>
            </form>

            <div class="mt-2 opacity-70 text-sm">Score: {{ score }}</div>

            <div
                class="flex flex-wrap mt-3 w-1/2 justify-center"
                v-if="prevGuessesList.length > 1"
            >
                <div
                    v-for="(prevGuess, index) in prevGuessesList
                        .slice()
                        .reverse()"
                >
                    <span class="font-semibold mx-1">{{ prevGuess }}</span>
                    <template v-if="index !== prevGuessesList.length - 1"
                        >🤜</template
                    >
                </div>
            </div>

            <div v-if="gameLost" class="w-full text-center">
                <Button
                    class="mt-10 w-1/2"
                    severity="info"
                    @click="restart"
                    label="Restart"
                />
            </div>
        </div>
    </div>
</template>

<style>
.p-message-error {
    word-break: break-word;
}
.p-toast {
    max-width: calc(100vw - 40px);
    word-break: break-word;
}
</style>
