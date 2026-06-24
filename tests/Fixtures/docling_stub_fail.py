#!/usr/bin/env python3
"""Stub de test : simule un échec de Docling (stderr + code non nul)."""
import sys

print("docling: conversion impossible", file=sys.stderr)
sys.exit(1)
